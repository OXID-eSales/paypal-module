<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

class LegacyModuleSettings
{
    public const LEGACY_MODULE_ID = 'module:oepaypal';

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
}
