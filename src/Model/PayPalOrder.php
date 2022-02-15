<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Application\Model\Article as EshopModelArticle;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;

class PayPalOrder extends BaseModel
{
    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'osc_paypal_order';

    /**
     * Construct initialize class
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    public function getPayPalOrderId(): string
    {
        return $this->getFieldData('oxpaypalorderid');
    }

    public function getShopOrderId(): string
    {
        return $this->getFieldData('oxorderid');
    }

    public function getStatus(): string
    {
        return $this->getFieldData('oscpaypalstatus');
    }

    public function getPaymentMethodId(): string
    {
        return $this->getFieldData('oscpaymentmethodid');
    }

    public function setStatus(string $status): void
    {
        $this->assign(
            [
                'oscpaypalstatus' => $status
            ]
        );
    }

    public function setPaymentMethodId(string $id): void
    {
        $this->assign(
            [
                'oscpaymentmethodid' => $id
            ]
        );
    }

}