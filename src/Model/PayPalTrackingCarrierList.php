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
     * @param string $countryCode - optional
     */
    public function loadTrackingCarriers(string $countryCode = '')
    {
        $baseObject = $this->getBaseObject();
        $viewName = $baseObject->getViewName();
        $select = "select * from {$viewName} where 1 ";
        $selectParams = [];
        if ($countryCode) {
            $select .= "and {$viewName}.oxcountrycode = :oxcountrycode";
            $selectParams[':oxcountrycode'] = $countryCode;
        }
        $this->selectString($select, $selectParams);
    }

    /**
     * Load allowed Tracking-Carrier Country-Codes
     *
     */
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
                $result[] = $row['oxcountrycode'];
            }
        }

        return $result;
    }
}
