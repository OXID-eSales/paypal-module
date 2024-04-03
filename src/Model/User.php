<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use DateTimeImmutable;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\RequiredAddressFields;
use OxidEsales\Eshop\Application\Model\Country as EshopModelCountry;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone as ApiModelPhone;
use \OxidEsales\Eshop\Application\Model\User_parent;

/**
 * PayPal oxOrder class
 *
 * @mixin \OxidEsales\Eshop\Application\Model\Order
 */
class User extends User_parent
{
    /**
     * @inheritDoc
     */
    public function onOrderExecute(mixed $basket, mixed $success): void
    {
        //this annotation below is for PHPStan, no other solution to meet all level 8 requirements yet
        /** @var Basket $basket*/

        // we manipulate the $success only for this parent onOrderExecute
        // to add the customers to the correct usergroup

        if (
            in_array($success, [
                Order::ORDER_STATE_ACDCINPROGRESS,
                Order::ORDER_STATE_ACDCCOMPLETED,
                Order::ORDER_STATE_NEED_CALL_ACDC_FINALIZE,
                Order::ORDER_STATE_SESSIONPAYMENT_INPROGRESS,
                Order::ORDER_STATE_TIMEOUT_FOR_WEBHOOK_EVENTS,
                Order::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS
            ], true) &&
            PayPalDefinitions::isPayPalPayment($basket->getPaymentId())
        ) {
            $success = 1;
        }
        parent::onOrderExecute($basket, $success);
    }

    public function getBirthDateForPuiRequest(): ?string
    {
        $required = EshopRegistry::getRequest()->getRequestParameter('pui_required');
        $day = $required['birthdate']['day'];
        $month = $required['birthdate']['month'];
        $year = $required['birthdate']['year'];

        $result = null;
        if (checkdate($month, $day, $year)) {
            $result = (new DateTimeImmutable())->setDate($year, $month, $day);
            $result = $result->format('Y-m-d');
        }

        return $result;
    }

    public function getPhoneNumberForPuiRequest(): ?ApiModelPhone
    {
        $result = null;
        $rawNumber = EshopRegistry::getRequest()->getRequestParameter('pui_required')['phonenumber'];

        $country = oxNew(EshopModelCountry::class);
        $country->load($this->getFieldData('oxcountryId'));
        $countryCode = $country->getFieldData('oxisoalpha2');
        $phoneUtils = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtils->parse($rawNumber, $countryCode);
            if ($phoneUtils->isValidNumber($phoneNumber)) {
                $result = new ApiModelPhone();
                $result->country_code = (string)$phoneNumber->getCountryCode();
                $result->national_number = (string)$phoneNumber->getNationalNumber();
            }
        } catch (NumberParseException $exception) {
            throw UserPhone::byRequestData();
        }

        return $result;
    }

    /**
     * get the InvoiceAddress from user with all required fields
     * @return array
     */
    public function getInvoiceAddress(): array
    {
        $result = [];
        $requiredAddressFields = oxNew(RequiredAddressFields::class);

        // Needed to not produce an error in InputValidator->hasRequiredParametersForVatInCheck()
        $requiredFields = $requiredAddressFields->getRequiredFields();
        $requiredFields[] = 'oxuser__oxustid';
        $requiredFields[] = 'oxuser__oxcountryid';
        $requiredFields[] = 'oxuser__oxcompany';
        $requiredAddressFields->setRequiredFields($requiredFields);

        foreach ($requiredAddressFields->getBillingFields() as $requiredAddressField) {
            $result[$requiredAddressField] = $this->{$requiredAddressField}->value;
        }

        return $result;
    }

    /**
     * @param string $userName
     *
     * @return false|string
     */
    private function getUserIdByPayPalAddress(string $userName)
    {
        return DatabaseProvider::getDb()->getOne(
            "SELECT `OXID` FROM oxuser
            WHERE 1 AND oxusername = :oxusername",
            [
                ':oxusername' => $userName
            ]
        );
    }

    /**
     * Login with PayPalUsername
     *
     * @param string $userName
     * @param string $password
     */
    protected function onLogin(mixed $userName, mixed $password): void
    {
        if (PayPalSession::isPayPalExpressOrderActive()) {
            $userId = $this->getUserIdByPayPalAddress($userName);
            if ($userId) {
                $this->load($userId);
            }
        } else {
            parent::onLogin($userName, $password);
        }
    }

    /**
     * Updates query for selecting orders.
     *
     * @param string $query
     *
     * @return string
     */
    protected function updateGetOrdersQuery($query)
    {
        $query = parent::updateGetOrdersQuery($query) . ' and oxordernr > 0 ';

        return $query;
    }
}
