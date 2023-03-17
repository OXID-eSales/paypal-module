<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\Model\ListModel;

class PayPalTrackingCarrier extends ListModel
{
    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'oscpaypal_trackingcarrier'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Construct initialize class
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    public function getCountryCode(): string
    {
        return (string) $this->getFieldData('oxcountrycode');
    }

    public function getTitle(): string
    {
        return (string) $this->getFieldData('oxtitle');
    }

    public function getKey(): string
    {
        return (string) $this->getFieldData('oxkey');
    }
}
