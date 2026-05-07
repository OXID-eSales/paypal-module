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

    /** @var array|null  */
    protected $trackingCarrierCountries = null;

    /** @var string|null */
    protected $effectiveTrackingCountryCode = null;

    /**
     * @throws ApiException
     * @throws StandardException
     */
    protected function onOrderSend()
    {
        parent::onOrderSend();
        if ($this->paidWithPayPal()) {
            if ($this->isPayPalOrderCaptureOnDelivery()) {
                $this->capturePayPalOrder();
            }
            $order = $this->getOrder();
            $order->doProvidePayPalTrackingCarrier();
        }
    }

    /**
     * @throws StandardException
     */
    public function save()
    {
        $request = Registry::getRequest();

        // Persist the PayPal tracking values BEFORE sendOrder() so the
        // onOrderSend hook can read them from oscpaypal_order when it calls
        // doProvidePayPalTrackingCarrier() — otherwise the tracking is never
        // pushed to the PayPal API on the same submit (0007945).
        $trackingCarrier = $request->getRequestParameter("paypaltrackingcarrier");
        $trackingCode = $request->getRequestParameter("paypaltrackingcode");
        if ($trackingCarrier && $trackingCode) {
            $this->getOrder()->setPayPalTracking(
                $trackingCarrier,
                $trackingCode
            );
        }

        if ($request->getRequestParameter("sendorder")) {
            $this->sendOrder();
        }

        parent::save();
    }

    /**
     * @throws StandardException
     */
    public function getPayPalTrackingCode(): string
    {
        return $this->getOrder()->getPayPalTrackingCode();
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
                    'selected' => ($this->getEffectiveTrackingCountryCode() === $allowedCountry)
                ];
            }
        }
        return $this->trackingCarrierCountries;
    }

    public function getPayPalTrackingCarrierProvider($countryCode = ''): array
    {
        $provider = $this->getPayPalDefaultCarrierSelection();
        $order = $this->getOrder();
        $savedTrackingCarrierId = $order->getPayPalTrackingCarrier();

        $countryCode = $countryCode ?: $this->getEffectiveTrackingCountryCode();

        if ($countryCode) {
            $trackingCarrierList = oxNew(PayPalTrackingCarrierList::class);
            $trackingCarrierList->loadTrackingCarriers($countryCode);
            if ($trackingCarrierList->count()) {
                foreach ($trackingCarrierList as $trackingCarrier) {
                    $trackingCarrierId = $trackingCarrier->getFieldData('oxkey');
                    $provider[$trackingCarrier->getId()] = [
                        'id'       => $trackingCarrier->getFieldData('oxkey'),
                        'title'    => $trackingCarrier->getFieldData('oxtitle'),
                        'selected' => $savedTrackingCarrierId === $trackingCarrierId
                    ];
                }
            }
        }

        return $provider;
    }

    public function getPayPalTrackingCarrierProviderAsJson(): void
    {
        $countryCode = (string)Registry::getRequest()->getRequestEscapedParameter('countrycode', '');
        $provider = $this->getPayPalTrackingCarrierProvider($countryCode);
        $this->outputJson($provider);
    }

    protected function getPayPalDefaultCarrierSelection()
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
        $order = $this->getOrder();

        $countryId = $order->getFieldData('oxdelcountryid') ?: $order->getFieldData('oxbillcountryid');
        $country = oxNew(Country::class);
        if ($country->load($countryId)) {
            $countryCode = $country->getFieldData('oxisoalpha2');
        }

        return $countryCode;
    }

    /**
     * Returns the country code that should be pre-selected in the tracking carrier country dropdown.
     * If a carrier is already saved, returns the country the carrier belongs to.
     * Otherwise falls back to the order's country.
     *
     * @throws StandardException
     */
    protected function getEffectiveTrackingCountryCode(): string
    {
        if (is_null($this->effectiveTrackingCountryCode)) {
            $savedCarrierKey = $this->getOrder()->getPayPalTrackingCarrier();
            $this->effectiveTrackingCountryCode = '';

            if ($savedCarrierKey) {
                $trackingCarrierList = oxNew(PayPalTrackingCarrierList::class);
                $this->effectiveTrackingCountryCode = $trackingCarrierList->getCountryCodeByCarrierKey($savedCarrierKey);
            }

            if (!$this->effectiveTrackingCountryCode) {
                $this->effectiveTrackingCountryCode = $this->getPayPalOrderCountryCode();
            }
        }

        return $this->effectiveTrackingCountryCode;
    }
}
