<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;

class UserRepository
{
    /** @var DatabaseProvider */
    private $db;

    /** @var EshopCoreConfig */
    private $config;

    public function __construct(
        EshopCoreConfig $config,
        Database $db
    ) {
        $this->config = $config;
        $this->db = $db;
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
}
