<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\DatabaseProvider;

class LegacyOeppModuleDetails
{
    public const LEGACY_MODULE_ID = 'oepaypal';

    /**
     * Determines whether the legacy PayPal module "oepaypal" is enabled
     * @return bool
     */
    public function isLegacyModulePresent(): bool
    {
        $oepaypalModule = oxNew(Module::class);
        if ($oepaypalModule->load(self::LEGACY_MODULE_ID))
        {
            return $oepaypalModule->isActive();
        }

        return false;
    }

    /**
     * @var string[] Array of the legacy settings with their corresponding settings in this module.
     */
    protected $transferrableSettings = [
        // old name => new name
        'oePayPalBannersHideAll'                    => 'oscPayPalBannersShowAll', // invert this value!
        'oePayPalBannersStartPage'                  => 'oscPayPalBannersStartPage',
        'oePayPalBannersStartPageSelector'          => 'oscPayPalBannersStartPageSelector',
        'oePayPalBannersCategoryPage'               => 'oscPayPalBannersCategoryPage',
        'oePayPalBannersCategoryPageSelector'       => 'oscPayPalBannersCategoryPageSelector',
        'oePayPalBannersSearchResultsPage'          => 'oscPayPalBannersSearchResultsPage',
        'oePayPalBannersSearchResultsPageSelector'  => 'oscPayPalBannersSearchResultsPageSelector',
        'oePayPalBannersProductDetailsPage'         => 'oscPayPalBannersProductDetailsPage',
        'oePayPalBannersProductDetailsPageSelector' => 'oscPayPalBannersProductDetailsPageSelector',
        'oePayPalBannersCheckoutPage'               => 'oscPayPalBannersCheckoutPage',
        'oePayPalBannersCartPageSelector'           => 'oscPayPalBannersCartPageSelector',
        'oePayPalBannersPaymentPageSelector'        => 'oscPayPalBannersPaymentPageSelector',
        'oePayPalBannersColorScheme'                => 'oscPayPalBannersColorScheme',
    ];

    /**
     * @return string[] Array of settings that can be moved from the old module to the new one
     */
    public function getTransferrableSettings(): array
    {
        return $this->transferrableSettings;
    }

    /**
     * Checks whether oepaypal and its transaction data tables are present
     * @return bool
     */
    public function showTransferTransactiondataButton(): bool
    {
        if (!$this->isLegacyModulePresent()) {
            return false;
        }

        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $out = $db->getAll(
            "SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?;",
            ['oepaypal_order']
        );

        return $out[0]['c'];
    }
}
