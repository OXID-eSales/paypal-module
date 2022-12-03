<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Events;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Application\Model\Payment as EshopModelPayment;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\StaticContent;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;

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

        //extend session required controller
        self::addRequireSession();
    }

    /**
     * Execute action on deactivate event
     *
     * @return void
     */
    public static function onDeactivate(): void
    {
        foreach (PayPalDefinitions::getPayPalDefinitions() as $paymentId => $paymentDefinitions) {
            $paymentMethod = oxNew(EshopModelPayment::class);
            if ($paymentMethod->load($paymentId)) {
                $paymentMethod->oxpayments__oxactive = new Field(false);
                $paymentMethod->save();
            }
        }
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
                            char(255)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            DEFAULT \'\'
                            COMMENT \'PayPal Status\',
                        `OSCPAYMENTMETHODID`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'PayPal payment id\',
                        `OSCPAYPALTRANSACTIONID`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            DEFAULT \'\'
                            COMMENT \'PayPal transaction id\',
                        `OSCPAYPALTRANSACTIONTYPE`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            DEFAULT \'capture\'
                            COMMENT \'PayPal transaction type\',
                        `OSCPAYPALTRACKINGID`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'PayPal tracking id\',
                        `OSCPAYPALTRACKINGTYPE`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'PayPal tracking type\',
                        `OSCPAYPALPUIPAYMENTREFERENCE`
                            char(32)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'PayPal Pui Payment Reference\',
                        `OSCPAYPALPUIBIC`
                            char(11)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'PayPal Pui Bic\',
                        `OSCPAYPALPUIIBAN`
                            char(22)
                            character set latin1
                            collate latin1_general_ci
                            NOT NULL
                            COMMENT \'PayPal Pui IBAN\',
                        `OSCPAYPALPUIBANKNAME`
                             varchar(255)
                             NOT NULL
                            COMMENT \'PayPal Pui Bankname\',
                        `OSCPAYPALPUIACCOUNTHOLDERNAME`
                            varchar(255)
                             NOT NULL
                            COMMENT \'PayPal Pui Account Holder Name\',
                       `OXTIMESTAMP`
                            timestamp
                            NOT NULL
                            default CURRENT_TIMESTAMP
                            on update CURRENT_TIMESTAMP
                            COMMENT \'Timestamp\',
                        PRIMARY KEY (`OXID`),
                        UNIQUE KEY `OXORDERID_OXPAYPALORDERID_OSCPAYPALTRANSACTIONID`
                            (`OXORDERID`,`OXPAYPALORDERID`, `OSCPAYPALTRANSACTIONID`))
                        ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
                        COMMENT \'Paypal Checkout\'',
            'oscpaypal_order'
        );

        DatabaseProvider::getDb()->execute($sql);

        // additional Module-Update v1.1.0

        if (!self::tableColumnExists('oscpaypal_order', 'OSCPAYPALPUIPAYMENTREFERENCE')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD `OSCPAYPALPUIPAYMENTREFERENCE` char(32) collate latin1_general_ci";
            DatabaseProvider::getDb()->execute($sql);
        }

        if (!self::tableColumnExists('oscpaypal_order', 'OSCPAYPALPUIBIC')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD `OSCPAYPALPUIBIC` char(11) collate latin1_general_ci";
            DatabaseProvider::getDb()->execute($sql);
        }

        if (!self::tableColumnExists('oscpaypal_order', 'OSCPAYPALPUIIBAN')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD `OSCPAYPALPUIIBAN` char(22) collate latin1_general_ci";
            DatabaseProvider::getDb()->execute($sql);
        }

        if (!self::tableColumnExists('oscpaypal_order', 'OSCPAYPALPUIBANKNAME')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD `OSCPAYPALPUIBANKNAME` varchar(255) NOT NULL";
            DatabaseProvider::getDb()->execute($sql);
        }

        if (!self::tableColumnExists('oscpaypal_order', 'OSCPAYPALPUIACCOUNTHOLDERNAME')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD `OSCPAYPALPUIACCOUNTHOLDERNAME` varchar(255) NOT NULL";
            DatabaseProvider::getDb()->execute($sql);
        }

        // additional Module-Update v1.1.6

        if (!self::tableColumnExists('oscpaypal_order', 'OSCPAYPALTRANSACTIONID')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD `OSCPAYPALTRANSACTIONID` char(32) collate latin1_general_ci";
            DatabaseProvider::getDb()->execute($sql);
        }
        if (!self::tableColumnExists('oscpaypal_order', 'OSCPAYPALTRACKINGID')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD `OSCPAYPALTRACKINGID` char(32) collate latin1_general_ci";
            DatabaseProvider::getDb()->execute($sql);
        }
        if (!self::tableColumnExists('oscpaypal_order', 'OSCPAYPALTRACKINGTYPE')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD `OSCPAYPALTRACKINGTYPE` char(32) collate latin1_general_ci";
            DatabaseProvider::getDb()->execute($sql);
        }
        if (self::tableIndexExists('oscpaypal_order', 'ORDERID_PAYPALORDERID')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                DROP INDEX `ORDERID_PAYPALORDERID`";
            DatabaseProvider::getDb()->execute($sql);
        }
        if (!self::tableIndexExists('oscpaypal_order', 'OXORDERID_OXPAYPALORDERID_OSCPAYPALTRANSACTIONID')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD UNIQUE `OXORDERID_OXPAYPALORDERID_OSCPAYPALTRANSACTIONID`
                (`OXORDERID`, `OXPAYPALORDERID`, `OSCPAYPALTRANSACTIONID`)";
            DatabaseProvider::getDb()->execute($sql);
        }

        // additional Module-Update v1.2.0

        if (!self::tableColumnExists('oscpaypal_order', 'OSCPAYPALTRANSACTIONTYPE')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                ADD `OSCPAYPALTRANSACTIONTYPE` char(32) collate latin1_general_ci DEFAULT \'capture\'";
            DatabaseProvider::getDb()->execute($sql);
        }

        if (self::tableColumnExists('oscpaypal_order', 'OSCPAYPALTRANSACTIONID')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                CHANGE `OSCPAYPALTRANSACTIONID` `OSCPAYPALTRANSACTIONID` varchar(32)
                COLLATE 'latin1_general_ci' NOT NULL DEFAULT '' COMMENT 'PayPal transaction id'";
            DatabaseProvider::getDb()->execute($sql);
        }

        if (self::tableColumnExists('oscpaypal_order', 'OSCPAYPALSTATUS')) {
            $sql = "ALTER TABLE `oscpaypal_order`
                CHANGE `OSCPAYPALSTATUS` `OSCPAYPALSTATUS` varchar(255)
                COLLATE 'latin1_general_ci' NOT NULL DEFAULT '' COMMENT 'PayPal Status'";
            DatabaseProvider::getDb()->execute($sql);
        }
    }

    /**
    * Check if table or table column exists
    *
    * @param  $tableName - Name of table
    * @param  $columnName - Name of Column
    *
    * @return boolean
    */
    private static function tableColumnExists($tableName = '', $columnName = '')
    {
        $result = false;
        if ($tableName && $columnName) {
            $db = DatabaseProvider::getDb();

            $results = $db->select(
                "show columns from {$tableName} like :columnName",
                [
                    ':columnName' => $columnName
                ]
            );
            if ($results != false && $results->count() > 0) {
                $result = true;
            }
        }
        return $result;
    }

    /**
    * Check if table or table column exists
    *
    * @param  $tableName - Name of table
    * @param  $indexName - Name of Index
    *
    * @return boolean
    */
    private static function tableIndexExists($tableName = '', $indexName = '')
    {
        $result = false;
        if ($tableName && $indexName) {
            $db = DatabaseProvider::getDb();

            $results = $db->select(
                "show index from {$tableName} where key_name like :indexName",
                [
                    ':indexName' => $indexName
                ]
            );
            if ($results != false && $results->count() > 0) {
                $result = true;
            }
        }
        return $result;
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

    /**
     * add details controller to requireSession
     */
    private static function addRequireSession(): void
    {
        $moduleSettings = new ModuleSettings(
            Registry::getConfig(),
            DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)
        );
        $moduleSettings->addRequireSession();
    }
}
