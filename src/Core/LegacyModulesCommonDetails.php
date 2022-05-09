<?php

namespace OxidSolutionCatalysts\PayPal\Core;

/*
use OxidEsales\Eshop\Core\Module\Module;
*/

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

class LegacyModulesCommonDetails
{
    use ServiceContainer;

    /** @var string ID as found in metadata.php */
    protected $legacyModuleId = 'empty';

    /** @var string Getter to return the module ID */
    public function getLegacyModuleId(): string
    {
        return $this->legacyModuleId;
    }

    /**
     * Determines whether the legacy PayPal module with the ID in $legacyModuleId is active
     * @return bool
     */
    public function isLegacyModuleActive(): bool
    {
        /* For OXID eShop <= 6.3
        $oepaypalModule = oxNew(Module::class);
        if ($oepaypalModule->load($this->legacyModuleId))
        {
            return $oepaypalModule->isActive();
        }

        return false;
        */

        $container = ContainerFactory::getInstance()->getContainer();
        $moduleActivationBridge = $container->get(ModuleActivationBridgeInterface::class);

        return $moduleActivationBridge->isActive(
            $this->legacyModuleId,
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
     * Gets transaction data from the legacy module's source table and transfers them to this module's core table.
     * @return void
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function transferTransactionData()
    {
        $this->updatePaymentKeys();

        $OrderModel = Registry::get(PayPalOrder::class);
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $oldRecords = $this->getOeppTransactionRecords();
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
}
