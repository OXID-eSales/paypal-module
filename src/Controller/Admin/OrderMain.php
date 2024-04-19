<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Model\PayPalTrackingCarrierList;
use OxidSolutionCatalysts\PayPal\Traits\AdminOrderTrait;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\RequestDataGetter;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;

/**
* OrderMain class
*
* @mixin \OxidEsales\Eshop\Application\Controller\Admin\OrderMain
*/
class OrderMain extends OrderMain_parent
{
    use AdminOrderTrait;
    use JsonTrait;
    use RequestDataGetter;

    /** @var array|null  */
    protected $trackingCarrierCountries = null;

    /**
     * @throws ApiException
     * @throws StandardException
     */
    protected function onOrderSend(): void
    {
        parent::onOrderSend();
        if ($this->isPayPalStandardOnDeliveryCapture()) {
            $this->capturePayPalStandard();
        }
        if ($this->paidWithPayPal()) {
            /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
            $order = $this->getOrder();
            $order->doProvidePayPalTrackingCarrier();
        }
    }

    /**
     * @throws StandardException
     */
    public function save(): void
    {
        $config = Registry::getConfig();
        if ($config->getRequestParameter("sendorder")) {
            $this->sendOrder();
        }
        $trackingCarrier = $config->getRequestParameter("paypaltrackingcarrier");
        $trackingCode = $config->getRequestParameter("paypaltrackingcode");
        if (is_string($trackingCarrier) && is_string($trackingCode)) {
            /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
            $order = $this->getOrder();
            $order->setPayPalTracking(
                $trackingCarrier,
                $trackingCode
            );
        }

        parent::save();
    }

    public function getPayPalTrackingCode(): string
    {
        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $order = $this->getOrder();
        return $order->getPayPalTrackingCode();
    }

    public function getPayPalTrackingCarrierCountries(): array
    {
        if (is_null($this->trackingCarrierCountries)) {
            $lang = Registry::getLang();
            $country = oxNew(Country::class);

            $this->trackingCarrierCountries = $this->getPayPalDefaultCarrierSelection();
            $trackingCarrierList = oxNew(PayPalTrackingCarrierList::class);
            $allowedCountries = $trackingCarrierList->getAllowedTrackingCarrierCountryCodes();
            foreach ($allowedCountries as $allowedCountry) {
                $countryId = $country->getIdByCode($allowedCountry);
                $countryTitle = $country->load($countryId) ?
                    $country->getFieldData('oxtitle') :
                    $lang->translateString('OSC_PAYPAL_TRACKCARRIER_' . $allowedCountry);
                $this->trackingCarrierCountries[$allowedCountry] = [
                    'id'       => $allowedCountry,
                    'title'    => $countryTitle,
                    'selected' => ($this->getPayPalOrderCountryCode() === $allowedCountry)
                ];
            }
        }
        return $this->trackingCarrierCountries;
    }

    public function getPayPalTrackingCarrierProvider(string $countryCode = ''): array
    {
        $provider = $this->getPayPalDefaultCarrierSelection();
        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $order = $this->getOrder();
        $savedTrackingCarrierId = $order->getPayPalTrackingCarrier();

        $countryCode = $countryCode ?: $this->getPayPalOrderCountryCode();

        if ($countryCode) {
            $trackingCarrierList = oxNew(PayPalTrackingCarrierList::class);
            $trackingCarrierList->loadTrackingCarriers($countryCode);
            if ($trackingCarrierList->count()) {
                /** @var \OxidSolutionCatalysts\PayPal\Model\PayPalTrackingCarrier $trackingCarrier */
                foreach ($trackingCarrierList as $trackingCarrier) {
                    $trackingCarrierId = $trackingCarrier->getPaypalStringData('oxkey');
                    $provider[$trackingCarrier->getId()] = [
                        'id'       => $trackingCarrier->getPaypalStringData('oxkey'),
                        'title'    => $trackingCarrier->getPaypalStringData('oxtitle'),
                        'selected' => $savedTrackingCarrierId === $trackingCarrierId
                    ];
                }
            }
        }

        return $provider;
    }

    public function getPayPalTrackingCarrierProviderAsJson(): void
    {
        $countryCode = self::getRequestStringParameter('countrycode', true);
        $provider = $this->getPayPalTrackingCarrierProvider($countryCode);
        $this->outputJson($provider);
    }

    protected function getPayPalDefaultCarrierSelection(): array
    {
        return [[
            'id'       => '',
            'title'    => '----',
            'selected' => false
        ]];
    }

    /**
     * @throws StandardException
     */
    public function getPayPalOrderCountryCode(): string
    {
        $countryCode = '';
        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $order = $this->getOrder();

        $countryId = $order->getPaypalStringData('oxdelcountryid') ?: $order->getPaypalStringData('oxbillcountryid');
        /** @var \OxidSolutionCatalysts\PayPal\Model\Country $country */
        $country = oxNew(Country::class);
        if ($country->load($countryId)) {
            $countryCode = $country->getPaypalStringData('oxisoalpha2');
        }

        return $countryCode;
    }
}
