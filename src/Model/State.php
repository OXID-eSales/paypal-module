<?php

namespace OxidSolutionCatalysts\PayPal\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use PDO;

class State extends State_parent
{
    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    public function loadByIdAndCountry($oxid, $countryID)
    {
        $parameters = [
            'oxid' => $oxid,
            'oxcountryid' => $countryID
        ];
        /** @var QueryBuilder $queryBuilder */
        $this->queryBuilderFactory = ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class);

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select('oxid')
            ->from('oxstates')
            ->where('oxid = :oxid')
            ->andWhere('oxcountryid = :oxcountryid');

        $objOxId = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return $this->load($objOxId);
    }
}
