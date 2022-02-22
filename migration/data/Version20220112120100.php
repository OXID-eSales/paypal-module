<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\ConfigFile;
use OxidEsales\Facts\Facts;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220112120100 extends AbstractMigration
{
    public function __construct($version)
    {
        parent::__construct($version);

        $this->platform->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up(Schema $schema): void
    {
        $this->createPayPalSubscriptionProductTable($schema);
        $this->createPayPalSubscriptionTable($schema);
        $this->createPayPalOrderTable($schema);
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * create subscription product table
     */
    protected function createPayPalSubscriptionProductTable(Schema $schema): void
    {
        if (!$schema->hasTable('osc_paypal_subscription_product')) {
            $subscriptionProduct = $schema->createTable('osc_paypal_subscription_product');
        } else {
            $subscriptionProduct = $schema->getTable('osc_paypal_subscription_product');
        }

        if (!$subscriptionProduct->hasColumn('OXID')) {
            $subscriptionProduct->addColumn(
                'OXID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci']
            );
        }
        if (!$subscriptionProduct->hasColumn('OXSHOPID')) {
            $subscriptionProduct->addColumn(
                'OXSHOPID',
                Types::INTEGER,
                ['columnDefinition' => 'int(11)', 'default' => 1, 'comment' => 'Shop ID (oxshops)']
            );
        }
        if (!$subscriptionProduct->hasColumn('OXARTID')) {
            $subscriptionProduct->addColumn(
                'OXARTID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci', 'comment' => 'OXID (oxarticles)']
            );
        }
        if (!$subscriptionProduct->hasColumn('PAYPALPRODUCTID')) {
            $subscriptionProduct->addColumn(
                'PAYPALPRODUCTID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci', 'comment' => 'PayPal product ID']
            );
        }
        if (!$subscriptionProduct->hasColumn('PAYPALSUBSCRIPTIONPLANID')) {
            $subscriptionProduct->addColumn(
                'PAYPALSUBSCRIPTIONPLANID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci', 'comment' => 'PayPal PLan ID']
            );
        }
        if (!$subscriptionProduct->hasColumn('OXTIMESTAMP')) {
            $subscriptionProduct->addColumn(
                'OXTIMESTAMP',
                Types::DATETIME_MUTABLE,
                ['columnDefinition' => 'timestamp default current_timestamp on update current_timestamp']
            );
        }
        if (!$subscriptionProduct->hasPrimaryKey('OXID')) {
            $subscriptionProduct->setPrimaryKey(['OXID']);
        }
    }

    /**
     * create subscription table
     */
    protected function createPayPalSubscriptionTable(Schema $schema): void
    {
        if (!$schema->hasTable('osc_paypal_subscription')) {
            $subscription = $schema->createTable('osc_paypal_subscription');
        } else {
            $subscription = $schema->getTable('osc_paypal_subscription');
        }

        if (!$subscription->hasColumn('OXID')) {
            $subscription->addColumn(
                'OXID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci']
            );
        }
        if (!$subscription->hasColumn('OXSHOPID')) {
            $subscription->addColumn(
                'OXSHOPID',
                Types::INTEGER,
                ['columnDefinition' => 'int(11)', 'default' => 1, 'comment' => 'Shop ID (oxshops)']
            );
        }
        if (!$subscription->hasColumn('OXUSERID')) {
            $subscription->addColumn(
                'OXUSERID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci', 'comment' => 'OXID (oxuser)']
            );
        }
        if (!$subscription->hasColumn('OXORDERID')) {
            $subscription->addColumn(
                'OXORDERID',
                Types::STRING,
                [
                    'columnDefinition' => 'char(32) collate latin1_general_ci',
                    'comment' => 'OXID Parent Order id (oxorder)'
                ]
            );
        }
        if (!$subscription->hasColumn('OXPARENTORDERID')) {
            $subscription->addColumn(
                'OXPARENTORDERID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci', 'comment' => 'OXID (oxorder)']
            );
        }
        if (!$subscription->hasColumn('OXPAYPALSUBPRODID')) {
            $subscription->addColumn(
                'OXPAYPALSUBPRODID',
                Types::STRING,
                [
                    'columnDefinition' => 'char(32) collate latin1_general_ci',
                    'comment' => 'OXID (osc_paypal_subscription_product)'
                ]
            );
        }
        if (!$subscription->hasColumn('PAYPALBILLINGAGREEMENTID')) {
            $subscription->addColumn(
                'PAYPALBILLINGAGREEMENTID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci', 'comment' => 'PayPal Billing Agreement ID']
            );
        }
        if (!$subscription->hasColumn('PAYPALBILLINGCYCLETYPE')) {
            $subscription->addColumn(
                'PAYPALBILLINGCYCLETYPE',
                Types::STRING,
                [
                    'columnDefinition' => 'char(10) collate latin1_general_ci',
                    'comment' => 'Billing Cycle Type (TRIAL, REGULAR)'
                ]
            );
        }
        if (!$subscription->hasColumn('PAYPALBILLINGCYCLENR')) {
            $subscription->addColumn(
                'PAYPALBILLINGCYCLENR',
                Types::STRING,
                ['columnDefinition' => 'int(10) unsigned', 'comment' => 'Billing Cycle Number']
            );
        }
        if (!$subscription->hasColumn('OXCANCELREQUESTSENDED')) {
            $subscription->addColumn(
                'OXCANCELREQUESTSENDED',
                Types::STRING,
                [
                    'columnDefinition' => 'tinyint(1) unsigned',
                    'comment' => 'Is there a cancel request send by the customer?'
                ]
            );
        }
        if (!$subscription->hasColumn('OXTIMESTAMP')) {
            $subscription->addColumn(
                'OXTIMESTAMP',
                Types::DATETIME_MUTABLE,
                ['columnDefinition' => 'timestamp default current_timestamp on update current_timestamp']
            );
        }
        if (!$subscription->hasPrimaryKey('OXID')) {
            $subscription->setPrimaryKey(['OXID']);
        }
    }

    /**
     * create paypal order table
     */
    protected function createPayPalOrderTable(Schema $schema): void
    {
        if (!$schema->hasTable('osc_paypal_order')) {
            $order = $schema->createTable('osc_paypal_order');
        } else {
            $order = $schema->getTable('osc_paypal_order');
        }

        if (!$order->hasColumn('OXID')) {
            $order->addColumn(
                'OXID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci']
            );
        }
        if (!$order->hasColumn('OXSHOPID')) {
            $order->addColumn(
                'OXSHOPID',
                Types::INTEGER,
                ['columnDefinition' => 'int(11)', 'default' => 1, 'comment' => 'Shop ID (oxshops)']
            );
        }
        if (!$order->hasColumn('OXORDERID')) {
            $order->addColumn(
                'OXORDERID',
                Types::STRING,
                [
                    'columnDefinition' => 'char(32) collate latin1_general_ci',
                    'comment' => 'OXID Parent Order id (oxorder)'
                ]
            );
        }
        if (!$order->hasColumn('OXPAYPALORDERID')) {
            $order->addColumn(
                'OXPAYPALORDERID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci', 'comment' => 'OXID (oxorder)']
            );
        }
        if (!$order->hasColumn('OSCPAYPALSTATUS')) {
            $order->addColumn(
                'OSCPAYPALSTATUS',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci', 'comment' => 'PAYPAL order status']
            );
        }
        if (!$order->hasColumn('OSCPAYMENTMETHODID')) {
            $order->addColumn(
                'OSCPAYMENTMETHODID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci', 'comment' => 'PayPal payment id']
            );
        }
        if (!$order->hasColumn('OXTIMESTAMP')) {
            $order->addColumn(
                'OXTIMESTAMP',
                Types::DATETIME_MUTABLE,
                ['columnDefinition' => 'timestamp default current_timestamp on update current_timestamp']
            );
        }
        if (!$order->hasPrimaryKey('OXID')) {
            $order->setPrimaryKey(['OXID']);
        }
        if (!$order->hasIndex('ORDERID_PAYPALORDERID')) {
            $order->addUniqueIndex(['OXORDERID', 'OXPAYPALORDERID'], 'ORDERID_PAYPALORDERID');
        }
    }
}
