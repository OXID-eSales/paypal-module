<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use PDO;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

class UserRepository
{
    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var ContextInterface */
    private $context;

    /** @var EshopCoreConfig */
    private $config;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ContextInterface $context,
        EshopCoreConfig $config
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->context = $context;
        $this->config = $config;
    }

    /**
     * Check if a user with password can be found by provided email for the current shop (or mall)
     */
    public function userAccountExists(string $userEmail): bool
    {
        $userId = $this->getUserId($userEmail);

        return empty($userId) ? false : true;
    }

    /**
     * Check if a user with password can be found by provided email for the current shop (or mall)
     */
    public function guestAccountExists(string $userEmail): bool
    {
        $userId = $this->getUserId($userEmail, false);

        return empty($userId) ? false : true;
    }

    private function getUserId(string $userEmail, bool $hasPassword = true): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxusername' => $userEmail
        ];

        $passWordCheck = $hasPassword ? 'LENGTH(`oxpassword`) > 0' : 'LENGTH(`oxpassword`) = 0';

        $queryBuilder->select('oxid')
            ->from('oxuser')
            ->where('oxusername = :oxusername')
            ->andWhere($passWordCheck);

        if (!$this->config->getConfigParam('blMallUsers')) {
            $queryBuilder->andWhere('oxshopid = :oxshopid');
            $parameters['oxshopid'] = $this->context->getCurrentShopId();
        }

        $userId = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string) $userId;
    }
}
