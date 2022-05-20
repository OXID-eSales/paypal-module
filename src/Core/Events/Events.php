<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Events;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\StaticContent;

class Events
{
    /**
     * Execute action on activate event
     */
    public static function onActivate(): void
    {
        // execute module migrations
        self::executeModuleMigrations();

        //add static contents and payment methods
        self::addStaticContents();
    }

    /**
     * Execute action on deactivate event
     *
     * @return void
     */
    public static function onDeactivate(): void
    {
    }

    /**
     * Execute necessary module migrations on activate event
     *
     * @return void
     */
    private static function executeModuleMigrations(): void
    {
        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                        `OXID`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'Record id\',
                        `OXSHOPID`
                             int(11)
                            DEFAULT 1
                            COMMENT \'Shop ID (oxshops)\',
                        `OXORDERID`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'OXID Parent Order id (oxorder)\',
                        `OXPAYPALORDERID`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'PayPal Transaction ID\',
                        `OSCPAYPALSTATUS`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'PayPal Status\',
                        `OSCPAYMENTMETHODID`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'PayPal payment id\',
                       `OXTIMESTAMP`
                            timestamp
                            NOT NULL
                            default CURRENT_TIMESTAMP
                            on update CURRENT_TIMESTAMP
                            COMMENT \'Timestamp\',
                        PRIMARY KEY (`OXID`),
                        UNIQUE KEY `ORDERID_PAYPALORDERID` (`OXORDERID`,`OXPAYPALORDERID`))
                        ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
                        COMMENT \'Paypal Checkout\'',
            'oscpaypal_order'
        );

        DatabaseProvider::getDb()->execute($sql);
    }

    /**
     * Execute necessary module migrations on activate event
     *
     * @return void
     */
    private static function addStaticContents(): void
    {
        $staticContent = new StaticContent(
            Registry::getConfig(),
            DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)
        );

        $staticContent->ensureStaticContents();
        $staticContent->ensurePayPalPaymentMethods();
    }
}
