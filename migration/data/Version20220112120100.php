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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220112120100 extends AbstractMigration
{
    public function __construct($version, $logger)
    {
        parent::__construct($version, $logger);

        $this->platform->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up(Schema $schema): void
    {
        $this->createPayPalOrderTable($schema);
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * create paypal order table
     */
    protected function createPayPalOrderTable(Schema $schema): void
    {
        if (!$schema->hasTable('oscpaypal_order')) {
            $order = $schema->createTable('oscpaypal_order');
        } else {
            $order = $schema->getTable('oscpaypal_order');
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
