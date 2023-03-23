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
final class Version20230316122302 extends AbstractMigration
{
    public function __construct($version)
    {
        parent::__construct($version);

        $this->platform->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up(Schema $schema): void
    {
        $this->createPayPalTrackingCarrierTable($schema);
        $this->updateOrderTable($schema);
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * update paypal order table
     */
    protected function createPayPalTrackingCarrierTable(Schema $schema): void
    {
        if (!$schema->hasTable('oscpaypal_trackingcarrier')) {
            $carrierTable = $schema->createTable('oscpaypal_trackingcarrier');
        } else {
            $carrierTable = $schema->getTable('oscpaypal_trackingcarrier');
        }

        if (!$carrierTable->hasColumn('OXID')) {
            $carrierTable->addColumn(
                'OXID',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci']
            );
        }
        if (!$carrierTable->hasColumn('OXCOUNTRYCODE')) {
            $carrierTable->addColumn(
                'OXCOUNTRYCODE',
                Types::STRING,
                [
                    'columnDefinition' => 'char(6)',
                    'comment' => 'OXID ISO-Code Countrycodes'
                ]
            );
        }
        if (!$carrierTable->hasColumn('OXTITLE')) {
            $carrierTable->addColumn(
                'OXTITLE',
                Types::STRING,
                [
                    'columnDefinition' => 'varchar(255)',
                    'comment' => 'Title of Tracking Carrier'
                ]
            );
        }
        if (!$carrierTable->hasColumn('OXKEY')) {
            $carrierTable->addColumn(
                'OXKEY',
                Types::STRING,
                [
                    'columnDefinition' => 'char(25)',
                    'comment' => 'Key of Tracking Carrier'
                ]
            );
        }
        if (!$carrierTable->hasColumn('OXTIMESTAMP')) {
            $carrierTable->addColumn(
                'OXTIMESTAMP',
                Types::DATETIME_MUTABLE,
                ['columnDefinition' => 'timestamp default current_timestamp on update current_timestamp']
            );
        }
        if (!$carrierTable->hasPrimaryKey('OXID')) {
            $carrierTable->setPrimaryKey(['OXID']);
        }
        if (!$carrierTable->hasIndex('OXKEY')) {
            $carrierTable->addUniqueIndex(['OXKEY'], 'OXKEY');
        }
        if (!$carrierTable->hasIndex('OXCOUNTRYCODE')) {
            $carrierTable->addIndex(['OXCOUNTRYCODE'], 'OXCOUNTRYCODE');
        }
    }

    /**
     * update paypal order table
     */
    protected function updateOrderTable(Schema $schema): void
    {
        $order = $schema->getTable('oxorder');
        if (!$order->hasColumn('OSCPAYPALTRACKINGCARRIER')) {
            $order->addColumn(
                'OSCPAYPALTRACKINGCARRIER',
                Types::STRING,
                [
                    'columnDefinition' => 'char(25)',
                    'comment' => 'PayPal: Key of Tracking Carrier'
                ]
            );
        }
    }
}
