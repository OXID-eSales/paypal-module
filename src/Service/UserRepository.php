<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\Eshop\Core\Session as EshopSession;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
class UserRepository
{
    /** @var DatabaseProvider */
    private $db;

    /** @var EshopCoreConfig */
    private $config;

    /** @var EshopRegistry */
    private $session;

    public function __construct(
        EshopCoreConfig $config,
        Database $db,
        EshopSession $session
    ) {
        $this->config = $config;
        $this->db = $db;
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

    private function getUserId(string $userEmail, bool $hasPassword = true): string
    {
        $passWordCheck = $hasPassword ? 'LENGTH(`oxpassword`) > 0' : 'LENGTH(`oxpassword`) = 0';
        $userId = "select oxid from oxuser where oxusername = :oxusername and oxshopid = :shopid and " . $passWordCheck;
        $type = $this->db->getOne($query, [
            ':oxusername' => $userEmail,
            ':shopid' => $this->config->getShopId()
        ]);

        return (string) $userId;
    }


    public function getUserCountryIso(): string
    {
        $result = '';
        if ($user = $this->session()->getUser()) {
            $country = oxNew(Country::class);
            $country->load($user->getFieldData('oxcountryid'));
            $result = (string) $country->getFieldData('oxisoalpha2');
        }
        return $result;
    }

    public function getUserStateIso(): string
    {
        $result = '';
        if ($user = $this->session()->getUser()) {
            $country = oxNew(State::class);
            $country->load($user->getFieldData('oxstateid'));
            $result = (string) $country->getFieldData('oxisoalpha2');
        }
        return $result;
    }
}
