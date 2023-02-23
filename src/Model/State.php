<?php

namespace OxidSolutionCatalysts\PayPal\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

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
        $queryBuilder->select('*')
            ->from('oxstates')
            ->where('oxid = :oxid')
            ->andWhere('oxcountryid = :oxcountryid');
        $state = $queryBuilder->setParameters($parameters)
            ->execute()
            ->fetchAllAssociative();
        if (!empty($state)) {
            $this->assign(array_shift($state));
        }
        return $state;
    }
}
