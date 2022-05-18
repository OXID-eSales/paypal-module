<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidEsales\Eshop\Application\Model\Content as EshopModelContent;
use OxidEsales\Eshop\Application\Model\Payment as EshopModelPayment;
use OxidEsales\Eshop\Core\Model\BaseModel as EshopBaseModel;
use OxidEsales\Eshop\Core\DatabaseProvider;

//NOTE: later we will do this on module installation, for now on first activation
class StaticContent
{
    public static function ensurePayPalPaymentMethods(): void
    {
        foreach (PayPalDefinitions::getPayPalDefinitions() as $paymentId => $paymentDefinitions) {
            $paymentMethod = oxNew(EshopModelPayment::class);
            if ($paymentMethod->load($paymentId)) {
                continue;
            }
            self::createPaymentMethod($paymentId, $paymentDefinitions);
            self::assignPaymentToCountries($paymentId, $paymentDefinitions['countries']);
            self::assignPaymentToActiveDeliverySets($paymentId);
        }
    }

    protected static function assignPaymentToActiveDeliverySets(string $paymentId): void
    {
        $deliverySetIds = self::getActiveDeliverySetIds();
        foreach ($deliverySetIds as $deliverySetId) {
            self::assignPaymentToDelivery($paymentId, $deliverySetId);
        }
    }

    protected static function assignPaymentToCountries(string $paymentId, array $countries): void
    {
        $activeCountriesIso2Id = array_flip(self::getActiveCountries());
        $assignToCountries = [];
        foreach ($countries as $countryIsoAlpha2) {
            if (isset($activeCountriesIso2Id[strtoupper($countryIsoAlpha2)])) {
                $assignToCountries[] = $activeCountriesIso2Id[strtoupper($countryIsoAlpha2)];
            }
        }
        $assignToCountries = empty($assignToCountries) ? $activeCountriesIso2Id : $assignToCountries;

        foreach ($assignToCountries as $countryId) {
            self::assignPaymentToCountry($paymentId, $countryId);
        }
    }

    protected static function assignPaymentToCountry(string $paymentId, string $countryId): void
    {
        $object2Paymentent = oxNew(EshopBaseModel::class);
        $object2Paymentent->init('oxobject2payment');
        $object2Paymentent->assign(
            [
                'oxpaymentid' => $paymentId,
                'oxobjectid'  => $countryId,
                'oxtype'      => 'oxcountry'
            ]
        );
        $object2Paymentent->save();
    }

    protected static function assignPaymentToDelivery(string $paymentId, string $deliverySetId): void
    {
        $object2Paymentent = oxNew(EshopBaseModel::class);
        $object2Paymentent->init('oxobject2payment');
        $object2Paymentent->assign(
            [
                'oxpaymentid' => $paymentId,
                'oxobjectid'  => $deliverySetId,
                'oxtype'      => 'oxdelset'
            ]
        );
        $object2Paymentent->save();
    }

    protected static function createPaymentMethod(string $paymentId, array $definitions): void
    {
        /** @var EshopModelPayment $paymentModel */
        $paymentModel = oxNew(EshopModelPayment::class);
        $paymentModel->setId($paymentId);

        $activeCountries = self::getActiveCountries();
        $iso2LanguageId = array_flip(self::getLanguageIds());

        $active = empty($definitions['countries']) ||
            0 < count(array_intersect($definitions['countries'], $activeCountries));
        $paymentModel->assign(
            [
               'oxactive' => (int) $active,
               'oxfromamount' => (int) $definitions['constraints']['oxfromamount'],
               'oxtoamount' => (int) $definitions['constraints']['oxtoamount'],
               'oxaddsumtype' => (string) $definitions['constraints']['oxaddsumtype']
            ]
        );
        $paymentModel->save();

        foreach ($definitions['descriptions'] as $langAbbr => $data) {
            if (!isset($iso2LanguageId[$langAbbr])) {
                continue;
            }
            $paymentModel->loadInLang($iso2LanguageId[$langAbbr], $paymentModel->getId());
            $paymentModel->assign(
                [
                    'oxdesc' => $data['desc'],
                    'oxlongdesc' => $data['longdesc']
                ]
            );
            $paymentModel->save();
        }
    }

    public static function ensureStaticContents(): void
    {
        foreach (PayPalDefinitions::getPayPalStaticContents() as $content) {
            $loadId = $content['oxloadid'];
            if (!self::needToAddContent($loadId)) {
                continue;
            }

            foreach (self::getLanguageIds() as $langId => $langAbbr) {
                $contentModel = self::getContentModel($loadId, $langId);
                $contentModel->assign(
                    [
                        'oxloadid'  => $loadId,
                        'oxactive'  => $content['oxactive'],
                        'oxtitle'   => $content['oxtitle_' . $langAbbr] ?? '',
                        'oxcontent' => $content['oxcontent_' . $langAbbr] ?? '',
                    ]
                );
                $contentModel->save();
            }
        }
    }

    protected static function needToAddContent(string $ident): bool
    {
        $content = oxNew(EshopModelContent::class);
        if ($content->loadByIdent($ident)) {
            return false;
        }
        return true;
    }

    protected static function getContentModel(string $ident, int $languageId): EshopModelContent
    {
        $content = oxNew(EshopModelContent::class);
        if ($content->loadByIdent($ident)) {
            $content->loadInLang($languageId, $content->getId());
        }

        return $content;
    }

    protected static function getActiveDeliverySetIds(): array
    {
        $fromDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll(
            'SELECT `oxid` FROM `oxdeliveryset` WHERE oxactive = 1'
        );

        foreach ($fromDb as $row) {
            $result[$row['oxid']] = $row['oxid'];
        }

        return $result;
    }

    /**
     * get the language-IDs
     */
    protected static function getLanguageIds(): array
    {
        return EshopRegistry::getLang()->getLanguageIds();
    }

    protected static function getActiveCountries(): array
    {
        $result = [];

        $fromDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll(
            'SELECT `oxid`, oxisoalpha2 FROM `oxcountry` WHERE oxactive = 1'
        );

        foreach ($fromDb as $row) {
            $result[$row['oxid']] = $row['oxisoalpha2'];
        }

        return $result;
    }
}
