<?php

namespace OxidSolutionCatalysts\PayPal\Traits;

/* For OXID eShop <= 6.1
use OxidEsales\Eshop\Core\Module\Module;
*/

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;

trait LegacyModulesCommonDetails
{
    use ServiceContainer;

    /**
     * Determines whether the legacy PayPal module with the ID in
     * @return bool
     */
    public function isLegacyModuleActive(): bool
    {
        /* For OXID eShop <= 6.1
        $oepaypalModule = oxNew(Module::class);
        if ($oepaypalModule->load(self::LEGACY_MODULE_ID))
        {
            return $oepaypalModule->isActive();
        }

        return false;
        */

        $container = ContainerFactory::getInstance()->getContainer();
        $moduleActivationBridge = $container->get(ModuleActivationBridgeInterface::class);

        return $moduleActivationBridge->isActive(
            self::LEGACY_MODULE_ID,
            Registry::getConfig()->getShopId()
        );
    }

    /**
     * @return string[] Getter for $this->transferrableTransactionData
     */
    protected function getTransferrableTransactionData(): array
    {
        return $this->transferrableTransactionData;
    }

    /**
     * Gets transaction data from the legacy module's source table and transfers them to the new module's core table.
     * @return void
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function transferTransactionData()
    {
        $this->updatePaymentKeys();

        $OrderModel = Registry::get(PayPalOrder::class);
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $oldRecords = $this->getTransactionRecords();
        $transactionDataFieldMapping = $this->getTransferrableTransactionData();
        $allowedKeys = array_keys($transactionDataFieldMapping);

        $amountOfRecords = 0;

        foreach ($oldRecords as $record) {
            $query = "INSERT IGNORE INTO `".$OrderModel->getCoreTableName()."` SET ";

            $last = count($record);
            $i = 0;
            foreach ($record as $key => $value)
            {
                if (in_array($key, $allowedKeys))
                {
                    if ($key == 'status')
                    {
                        $value = strtoupper($value);
                    }

                    $query .= "`".$transactionDataFieldMapping[$key]."` = '".$value."'";
                    if ($i < $last - 1)
                    {
                        $query .= ", ";
                    }
                }
                $i++;
            }

            $query .= ";";
            $result = $db->execute($query);

            if (is_numeric($result))
            {
                $amountOfRecords++;
            }
        }

        if ($amountOfRecords)
        {
            Registry::getUtilsView()->addErrorToDisplay(
                Registry::getLang()->translateString('OSC_PAYPAL_TRANSFERLEGACY_SUCCESS_RECORDS')." ".$amountOfRecords,
                false,
                true
            );
        }
    }

    /**
     * @var string[] Array of query results performed by getTransactionRecords(), translated for osc_paypal
     */
    protected $transferrableTransactionData = [
        // query keys   =>  oscpaypal_order
        'recordid'      =>  'OXID',
        'shopid'        =>  'OXSHOPID',
        'orderid'       =>  'OXORDERID',
        'transactionid' =>  'OXPAYPALORDERID',
        'status'        =>  'OSCPAYPALSTATUS',
        'paymenttype'   =>  'OSCPAYMENTMETHODID',
    ];

    /**
     * Update usages of old payment keys in OXID's tables
     * @return void
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function updatePaymentKeys()
    {
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $db->execute(
            "UPDATE `oxorder` SET `OXPAYMENTTYPE` = ? WHERE `OXPAYMENTTYPE` = ?;",
            [
                PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
                self::LEGACY_PAYMENT_ID
            ]
        );
        $db->execute(
            "UPDATE `oxuserpayments` SET `OXPAYMENTSID` = ? WHERE `OXPAYMENTSID` = ?;",
            [
                PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
                self::LEGACY_PAYMENT_ID
            ]
        );
    }
}
