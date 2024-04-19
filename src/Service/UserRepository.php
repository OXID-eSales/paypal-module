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
use OxidEsales\Eshop\Core\Session as EshopCoreSession;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use Doctrine\DBAL\ForwardCompatibility\Result;

class UserRepository
{
    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var ContextInterface */
    private $context;

    /** @var EshopCoreConfig */
    private $config;

    /** @var EshopCoreSession */
    private $session;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ContextInterface $context,
        EshopCoreConfig $config,
        EshopCoreSession $session
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->context = $context;
        $this->config = $config;
        $this->session = $session;
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

    private function getUserId(string $userEmail, bool $hasPassword = true): ?string
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

        $result = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute();

        if ($result instanceof Result) {
            $id = $result->fetchOne();
            if ($id !== '' && is_string($id)) {
                return $id;
            }
        }

        return null;
    }

    public function getUserCountryIso(): string
    {
        $user = $this->session->getUser();
        $country = oxNew(Country::class);
        $countryId = $user->getFieldData('oxcountryid');
        if (is_string($countryId)) {
            $country->load($countryId);
        }
        $iso = $country->getFieldData('oxisoalpha2');

        return is_string($iso) ? $iso : '';
    }

    public function getUserStateIso(): string
    {
        $user = $this->session->getUser();
        $state = oxNew(State::class);
        /** @phpstan-ignore-next-line */
        $state->loadByIdAndCountry(
            $user->getFieldData('oxstateid'),
            $user->getFieldData('oxcountryid')
        );
        $iso = $state->getFieldData('oxisoalpha2');

        return is_string($iso) ? $iso : '';
    }
}
