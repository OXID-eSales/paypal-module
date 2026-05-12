<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

class PayPalTrackingCarrierList extends ListModel
{
    use ServiceContainer;

    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName // phpcs:ignore PSR2.Classes.PropertyDeclaration
        = 'OxidSolutionCatalysts\PayPal\Model\PayPalTrackingCarrier';

    /**
     * Load Tracking-Carrier models
     *
     * For a country-scoped call the result also includes the carriers PayPal
     * lists under the "GLOBAL" bucket — those are carriers (DHL, FedEx, UPS,
     * Hermes-World, etc.) that PayPal stopped attaching to individual countries
     * but that are still relevant for every country-specific shipment. (0007945)
     *
     * @param string $countryCode - optional
     */
    public function loadTrackingCarriers(string $countryCode = '')
    {
        $baseObject = $this->getBaseObject();
        $viewName = $baseObject->getViewName();
        $select = "select * from {$viewName} where 1 ";
        $selectParams = [];
        if ($countryCode && $countryCode !== 'GLOBAL') {
            $select .= "and {$viewName}.oxcountrycode in (:oxcountrycode, 'GLOBAL') ";
            $selectParams[':oxcountrycode'] = $countryCode;
        } elseif ($countryCode) {
            $select .= "and {$viewName}.oxcountrycode = :oxcountrycode ";
            $selectParams[':oxcountrycode'] = $countryCode;
        }
        $select .= "order by {$viewName}.oxtitle asc";
        $this->selectString($select, $selectParams);
    }

    /**
     * Load allowed Tracking-Carrier Country-Codes
     *
     */
    /**
     * Find the country code for a given carrier key
     *
     * @param string $carrierKey
     * @return string empty string if not found
     */
    public function getCountryCodeByCarrierKey(string $carrierKey): string
    {
        if (!$carrierKey) {
            return '';
        }

        $baseObject = $this->getBaseObject();
        $viewName = $baseObject->getViewName();
        $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $queryBuilderFactory->create();

        /** @var Result $resultDB */
        $resultDB = $queryBuilder->select('oxcountrycode')
            ->from($viewName)
            ->where('oxkey = :oxkey')
            ->setParameter(':oxkey', $carrierKey)
            ->setMaxResults(1)
            ->execute();

        if (is_a($resultDB, Result::class)) {
            $row = $resultDB->fetchAssociative();
            if ($row) {
                $code = (string) $row['oxcountrycode'];
                // The "GLOBAL" bucket is not a country — let the caller fall
                // back to the order's own country code instead, so the admin
                // dropdown does not pre-select an invented entry. (0007945)
                if ($code === 'GLOBAL') {
                    return '';
                }
                return $code;
            }
        }

        return '';
    }

    public function getAllowedTrackingCarrierCountryCodes(): array
    {
        $result = [];

        $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $queryBuilderFactory->create();
        $inQueryBuilder = $queryBuilderFactory->create();
        $notInQueryBuilder = $queryBuilderFactory->create();

        $inQueryBuilder->select('oxisoalpha2')
            ->from('oxcountry')
            ->where('oxactive = 1');

        $notInQueryBuilder->select('oxisoalpha2')
            ->from('oxcountry');

        /** @var Result $resultDB */
        $resultDB = $queryBuilder->select('count(oxid), oxcountrycode')
            ->from('oscpaypal_trackingcarrier')
            ->where($queryBuilder->expr()->in('CONVERT(oxcountrycode USING utf8)', $inQueryBuilder->getSQL()))
            ->orWhere($queryBuilder->expr()->notIn('CONVERT(oxcountrycode USING utf8)', $notInQueryBuilder->getSQL()))
            ->groupBy('oxcountrycode')
            ->execute();

        if (is_a($resultDB, Result::class)) {
            $fromDB = $resultDB->fetchAllAssociative();
            foreach ($fromDB as $row) {
                // Skip the "GLOBAL" bucket — it's not a country code and must
                // not appear in the admin country dropdown. Its carriers are
                // automatically merged into every country-scoped lookup via
                // loadTrackingCarriers(). (0007945)
                if ($row['oxcountrycode'] === 'GLOBAL') {
                    continue;
                }
                $result[] = $row['oxcountrycode'];
            }
        }

        return $result;
    }
}
