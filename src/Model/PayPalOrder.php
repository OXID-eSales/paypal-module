<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\Model\BaseModel;

class PayPalOrder extends BaseModel
{
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
        return (string) $this->getFieldData('oxpaypalorderid');
    }

    public function getTransactionId(): string
    {
        return (string) $this->getFieldData('oscpaypaltransactionid');
    }

    public function getTrackingId(): string
    {
        return (string) $this->getFieldData('oscpaypaltrackingid');
    }

    public function getTrackingType(): string
    {
        return (string) $this->getFieldData('oscpaypaltrackingtype');
    }

    public function getShopOrderId(): string
    {
        return (string) $this->getFieldData('oxorderid');
    }

    public function getStatus(): string
    {
        return (string) $this->getFieldData('oscpaypalstatus');
    }

    public function getPaymentMethodId(): string
    {
        return (string) $this->getFieldData('oscpaymentmethodid');
    }

    public function setStatus(string $status)
    {
        $this->assign(
            [
                'oscpaypalstatus' => $status
            ]
        );
    }

    public function setTransactionId(string $id)
    {
        $this->assign(
            [
                'oscpaypaltransactionid' => $id
            ]
        );
    }

    public function setTrackingId(string $id)
    {
        $this->assign(
            [
                'oscpaypaltrackingid' => $id
            ]
        );
    }

    public function setTrackingType(string $type)
    {
        $this->assign(
            [
                'oscpaypaltrackingtype' => $type
            ]
        );
    }

    public function setPaymentMethodId(string $id)
    {
        $this->assign(
            [
                'oscpaymentmethodid' => $id
            ]
        );
    }

    public function setPuiPaymentReference(string $puiPaymentReference)
    {
        $this->assign(
            [
                'oscpaypalpuipaymentreference' => $puiPaymentReference
            ]
        );
    }

    public function setPuiBic(string $puiBic)
    {
        $this->assign(
            [
                'oscpaypalpuibic' => $puiBic
            ]
        );
    }

    public function setPuiIban(string $puiIban)
    {
        $this->assign(
            [
                'oscpaypalpuiiban' => $puiIban
            ]
        );
    }

    public function setPuiBankName(string $puiBankName)
    {
        $this->assign(
            [
                'oscpaypalpuibankname' => $puiBankName
            ]
        );
    }

    public function setPuiAccountHolderName(string $puiAccountHolderName)
    {
        $this->assign(
            [
                'oscpaypalpuiaccountholdername' => $puiAccountHolderName
            ]
        );
    }

    public function setTransactionType(string $type)
    {
        $this->assign(
            [
                'oscpaypaltransactiontype' => $type
            ]
        );
    }
}
