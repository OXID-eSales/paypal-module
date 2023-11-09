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
final class Version20220601140200 extends AbstractMigration
{
    public function __construct($version, $logger)
    {
        parent::__construct($version, $logger);

        $this->platform->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up(Schema $schema): void
    {
        $this->updatePayPalOrderTable($schema);
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * update paypal order table
     */
    protected function updatePayPalOrderTable(Schema $schema): void
    {
        $order = $schema->getTable('oscpaypal_order');

        if (!$order->hasColumn('OSCPAYPALPUIPAYMENTREFERENCE')) {
            $order->addColumn(
                'OSCPAYPALPUIPAYMENTREFERENCE',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci']
            );
        }
        if (!$order->hasColumn('OSCPAYPALPUIBIC')) {
            $order->addColumn(
                'OSCPAYPALPUIBIC',
                Types::STRING,
                ['columnDefinition' => 'char(11) collate latin1_general_ci']
            );
        }
        if (!$order->hasColumn('OSCPAYPALPUIIBAN')) {
            $order->addColumn(
                'OSCPAYPALPUIIBAN',
                Types::STRING,
                ['columnDefinition' => 'char(22) collate latin1_general_ci']
            );
        }
        if (!$order->hasColumn('OSCPAYPALPUIBANKNAME')) {
            $order->addColumn(
                'OSCPAYPALPUIBANKNAME',
                Types::STRING,
                ['columnDefinition' => 'varchar(255) NOT NULL']
            );
        }
        if (!$order->hasColumn('OSCPAYPALPUIACCOUNTHOLDERNAME')) {
            $order->addColumn(
                'OSCPAYPALPUIACCOUNTHOLDERNAME',
                Types::STRING,
                ['columnDefinition' => 'varchar(255) NOT NULL']
            );
        }
    }
}
