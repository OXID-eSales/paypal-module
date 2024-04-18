<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidSolutionCatalysts\PayPal\Traits\DataGetter;

class PayPalOrder extends BaseModel
{
    use DataGetter;

    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'oscpaypal_order'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

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
        return $this->getPaypalStringData('oxpaypalorderid');
    }

    public function getTransactionId(): string
    {
        return $this->getPaypalStringData('oscpaypaltransactionid');
    }

    public function getTrackingCode(): string
    {
        return $this->getPaypalStringData('oscpaypaltrackingid');
    }

    public function getTrackingCarrier(): string
    {
        return $this->getPaypalStringData('oscpaypaltrackingtype');
    }

    public function getShopOrderId(): string
    {
        return $this->getPaypalStringData('oxorderid');
    }

    public function getStatus(): string
    {
        return $this->getPaypalStringData('oscpaypalstatus');
    }

    public function getPaymentMethodId(): string
    {
        return $this->getPaypalStringData('oscpaymentmethodid');
    }

    public function setStatus(string $status): void
    {
        $this->assign(
            [
                'oscpaypalstatus' => $status
            ]
        );
    }

    public function setTransactionId(string $id): void
    {
        $this->assign(
            [
                'oscpaypaltransactionid' => $id
            ]
        );
    }

    public function setTrackingCode(string $id): void
    {
        $this->assign(
            [
                'oscpaypaltrackingid' => $id
            ]
        );
    }

    public function setTrackingCarrier(string $type): void
    {
        $this->assign(
            [
                'oscpaypaltrackingtype' => $type
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

    public function setPuiPaymentReference(string $puiPaymentReference): void
    {
        $this->assign(
            [
                'oscpaypalpuipaymentreference' => $puiPaymentReference
            ]
        );
    }

    public function setPuiBic(string $puiBic): void
    {
        $this->assign(
            [
                'oscpaypalpuibic' => $puiBic
            ]
        );
    }

    public function setPuiIban(string $puiIban): void
    {
        $this->assign(
            [
                'oscpaypalpuiiban' => $puiIban
            ]
        );
    }

    public function setPuiBankName(string $puiBankName): void
    {
        $this->assign(
            [
                'oscpaypalpuibankname' => $puiBankName
            ]
        );
    }

    public function setPuiAccountHolderName(string $puiAccountHolderName): void
    {
        $this->assign(
            [
                'oscpaypalpuiaccountholdername' => $puiAccountHolderName
            ]
        );
    }

    public function setTransactionType(string $type): void
    {
        $this->assign(
            [
                'oscpaypaltransactiontype' => $type
            ]
        );
    }
}
