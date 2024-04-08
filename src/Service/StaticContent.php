<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use Doctrine\DBAL\Driver\Exception;
use PDO;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Core\Field;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidEsales\Eshop\Application\Model\Content as EshopModelContent;
use OxidEsales\Eshop\Application\Model\Payment as EshopModelPayment;
use OxidEsales\Eshop\Core\Model\BaseModel as EshopBaseModel;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Doctrine\DBAL\ForwardCompatibility\Result;

//NOTE: later we will do this on module installation, for now on first activation
class StaticContent
{
    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var ModuleSettings */
    private $moduleSettings;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ModuleSettings $moduleSettings
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->moduleSettings = $moduleSettings;
    }

    public function ensurePayPalPaymentMethods(): void
    {
        foreach (PayPalDefinitions::getPayPalDefinitions() as $paymentId => $paymentDefinitions) {
            $paymentMethod = oxNew(EshopModelPayment::class);
            if ($paymentMethod->load($paymentId)) {
                $this->reActivatePaymentMethod($paymentId);
                continue;
            }
            $this->createPaymentMethod($paymentId, $paymentDefinitions);
            $this->assignPaymentToActiveDeliverySets($paymentId);
        }
    }

    protected function assignPaymentToActiveDeliverySets(string $paymentId): void
    {
        $deliverySetIds = $this->getActiveDeliverySetIds();
        foreach ($deliverySetIds as $deliverySetId) {
            $this->assignPaymentToDelivery($paymentId, $deliverySetId);
        }
    }

    protected function assignPaymentToDelivery(string $paymentId, string $deliverySetId): void
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

    protected function createPaymentMethod(string $paymentId, array $definitions): void
    {
        /** @var EshopModelPayment $paymentModel */
        $paymentModel = oxNew(EshopModelPayment::class);
        $paymentModel->setId($paymentId);

        $iso2LanguageId = array_flip($this->getLanguageIds());

        $paymentModel->assign(
            [
               'oxactive' => (bool) $definitions['defaulton'],
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

    protected function reActivatePaymentMethod(string $paymentId): void
    {
        $activePayments = $this->moduleSettings->getActivePayments();
        if (!in_array($paymentId, $activePayments, true)) {
            return;
        }

        /** @var EshopModelPayment $paymentModel */
        $paymentModel = oxNew(EshopModelPayment::class);
        $paymentModel->load($paymentId);

        if (property_exists($paymentModel, 'oxpayments__oxactive')) {
            $paymentModel->oxpayments__oxactive = new Field(true);
        }

        $paymentModel->save();
    }

    public function ensureStaticContents(): void
    {
        foreach (PayPalDefinitions::getPayPalStaticContents() as $content) {
            $loadId = $content['oxloadid'];
            if (!$this->needToAddContent($loadId)) {
                continue;
            }

            foreach ($this->getLanguageIds() as $langId => $langAbbr) {
                $contentModel = $this->getContentModel($loadId, $langId);
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

    protected function needToAddContent(string $ident): bool
    {
        $content = oxNew(EshopModelContent::class);
        if ($content->loadByIdent($ident)) {
            return false;
        }
        return true;
    }

    protected function getContentModel(string $ident, int $languageId): EshopModelContent
    {
        $content = oxNew(EshopModelContent::class);
        if ($content->loadByIdent($ident)) {
            $content->loadInLang($languageId, $content->getId());
        }

        return $content;
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getActiveDeliverySetIds(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();
        $return = [];
        $result = $queryBuilder
            ->select('oxid')
            ->from('oxdeliveryset')
            ->where('oxactive = 1')
            ->execute();

        if ($result instanceof Result) {
            $results = $result->fetchAllAssociative();
            foreach ($results as $row) {
                $return[$row['oxid']] = $row['oxid'];
            }
        }

        return $return;
    }

    /**
     * get the language-IDs
     */
    protected function getLanguageIds(): array
    {
        return EshopRegistry::getLang()->getLanguageIds();
    }
}
