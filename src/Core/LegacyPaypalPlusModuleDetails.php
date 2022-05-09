<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;

class LegacyPaypalPlusModuleDetails extends LegacyModulesCommonDetails
{
    /** @var string ID as found in metadata.php */
    protected $legacyModuleId = 'payppaypalplus';

    /**
     * Checks whether oepaypal and its transaction data tables are present and the transfer hasn't been executed yet.
     * @return bool
     */
    public function showTransferTransactiondataButton(): bool
    {
        if (!$this->isLegacyModuleActive()) {
            return false;
        }

        if ($this->getServiceFromContainer(ModuleSettings::class)->getLegacyPaypPlusTransactionsTransferStatus())
        {
            return false;
        }

        // @Todo Check for plus tables

        return true;
    }

    /**
     * @return void
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function transferTransactionData()
    {
        $this->updatePaymentKeys();

        // @Todo
    }

    /**
     * Update usages of old payment keys
     * @return void
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function updatePaymentKeys()
    {
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        // @Todo Transfer payment keys
    }
}
