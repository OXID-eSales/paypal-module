<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220901134100 extends AbstractMigration
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
        // Check if table exists first
        if (!$schema->hasTable('oscpaypal_order')) {
            return;
        }

        $order = $schema->getTable('oscpaypal_order');

        // Fix: Replace changeColumn() with raw SQL using addSql()
        if ($order->hasColumn('OSCPAYPALTRANSACTIONID')) {
            $this->addSql('ALTER TABLE oscpaypal_order MODIFY COLUMN OSCPAYPALTRANSACTIONID VARCHAR(255) NOT NULL DEFAULT ""');
        }

        if ($order->hasColumn('OSCPAYPALSTATUS')) {
            $this->addSql('ALTER TABLE oscpaypal_order MODIFY COLUMN OSCPAYPALSTATUS VARCHAR(255) COLLATE latin1_general_ci NOT NULL DEFAULT ""');
        }
    }
}
