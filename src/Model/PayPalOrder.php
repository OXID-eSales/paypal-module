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

    /**
     * Sets unique object id
     * Needed for Unit-Tests
     * Adds an underscore to created PayPal order entities while unit testing to mark them as test-orders
     * Which will be removed by cleanUpTable
     *
     * @param string $oxid Record ID
     *
     * @return string
     */
    public function setId($oxid = null)
    {
        $return = parent::setId($oxid);
        if (defined('OXID_PHP_UNIT')) {
            $oxid = $this->getId();
            if (strpos($oxid, '_') === false) {
                $oxid = "_".substr($oxid, 1);
                $return = parent::setId($oxid);
            }
        }
        return $return;
    }

    public function getPayPalOrderId(): string
    {
        return (string) $this->getFieldData('oxpaypalorderid');
    }

    public function getTransactionId(): string
    {
        return (string) $this->getFieldData('oscpaypaltransactionid');
    }

    public function getTrackingCode(): string
    {
        return (string) $this->getFieldData('oscpaypaltrackingid');
    }

    public function getTrackingCarrier(): string
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
