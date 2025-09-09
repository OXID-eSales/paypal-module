<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use DateTimeImmutable;
use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberParseException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Application\Model\RequiredAddressFields;
use OxidEsales\Eshop\Application\Model\Country as EshopModelCountry;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone as ApiModelPhone;

/**
 * PayPal oxOrder class
 *
 * @mixin \OxidEsales\Eshop\Application\Model\User
 */
class User extends User_parent
{
    use ServiceContainer;

    /**
     * @inheritDoc
     */
    public function onOrderExecute($basket, $success)
    {
        // we manipulate the $success only for this parent onOrderExecute
        // to add the customers to the correct usergroup

        if (
            in_array(
                $success,
                [
                Order::ORDER_STATE_ACDCINPROGRESS,
                Order::ORDER_STATE_ACDCCOMPLETED,
                Order::ORDER_STATE_NEED_CALL_ACDC_FINALIZE,
                Order::ORDER_STATE_SESSIONPAYMENT_INPROGRESS,
                Order::ORDER_STATE_TIMEOUT_FOR_WEBHOOK_EVENTS,
                Order::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS
                ],
                true
            )
            && PayPalDefinitions::isPayPalPayment($basket->getPaymentId())
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
        $rawNumber = trim(
            str_replace(
                ['+', '-', '(', ')', ' '],
                '',
                EshopRegistry::getRequest()->getRequestParameter('pui_required')['phonenumber']
            )
        );

        $country = oxNew(EshopModelCountry::class);
        $country->load($this->getFieldData('oxcountryId'));
        $countryCode = $country->getFieldData('oxisoalpha2');

        if (empty($countryCode) || strlen($countryCode) !== 2) {
            $countryCode = 'DE';
        }

        try {
            $phoneNumber = PhoneNumber::parse($rawNumber, $countryCode);
            $result = new ApiModelPhone();
            $result->country_code = $phoneNumber->getCountryCode();
            $result->national_number = $phoneNumber->getNationalNumber();
        } catch (PhoneNumberParseException $exception) {
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
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getUserIdByPayPalAddress(string $userName)
    {
        $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $queryBuilderFactory->create();

        $query = $queryBuilder
            ->select('OXID')
            ->from('oxuser')
            ->where('oxusername = :oxusername');

        $parameters = [
            ':oxusername' => $userName
        ];

        return $query->setParameters($parameters)->execute()->fetchOne();
    }

    /**
     * Login with PayPalUsername
     *
     * @param string $userName
     * @param string $password
     */
    protected function onLogin($userName, $password)
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
     * When changing/updating user information in frontend this method validates user
     * input. If data is fine - automatically assigns this values. If some action
     * fails - exception is thrown.
     *
     * @param string $sUser user login name
     * @param array $aInvAddress user billing address
     * @param array $aDelAddress delivery address
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws StandardException
     */
    public function changePayPalUserData(string $sUser, array $aInvAddress, array $aDelAddress): void
    {
        // validating values before saving. If validation fails - exception is thrown
        $this->checkValues($sUser, '', '', $aInvAddress, $aDelAddress);

        // input data is fine - lets save updated user info
        $this->assign($aInvAddress);

        if (count($aDelAddress)) {

            $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $queryBuilderFactory->create();

            $queryBuilder
                ->select('OXID')
                ->from('oxaddress')
                ->where('OXUSERID = :userid')
                ->andWhere('OXFNAME = :fname')
                ->andWhere('OXLNAME = :lname')
                ->andWhere('OXSTREET = :street')
                ->andWhere('OXSTREETNR = :streetnr')
                ->andWhere('OXCITY = :city')
                ->andWhere('OXCOUNTRYID = :countryid')
                ->andWhere('OXZIP = :zip');
            $queryBuilder->setParameters([
                'userid'    => $this->getId(),
                'fname'     => $aDelAddress['oxaddress__oxfname'],
                'lname'     => $aDelAddress['oxaddress__oxlname'],
                'street'    => $aDelAddress['oxaddress__oxstreet'],
                'streetnr'  => $aDelAddress['oxaddress__oxstreetnr'],
                'city'      => $aDelAddress['oxaddress__oxcity'],
                'countryid' => $aDelAddress['oxaddress__oxcountryid'],
                'zip'       => $aDelAddress['oxaddress__oxzip']
            ]);

            $result = $queryBuilder->execute();
            $sAddressId = $result->fetchOne();

            $oAddress = oxNew(Address::class);
            if ($sAddressId) {
                $oAddress->setId($sAddressId);
                $oAddress->load($sAddressId);
            }
            $oAddress->assign($aDelAddress);
            $oAddress->save();

            // resetting addresses
            $this->_aAddresses = null;

            // saving delivery Address for later use
            Registry::getSession()->setVariable('deladrid', $oAddress->getId());
        } else {
            // resetting
            Registry::getSession()->setVariable('deladrid', null);
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
