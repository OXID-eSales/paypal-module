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
final class Version20220921115200 extends AbstractMigration
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
        if (!$order->hasColumn('OSCPAYPALTRANSACTIONTYPE')) {
            $order->addColumn(
                'OSCPAYPALTRANSACTIONTYPE',
                Types::STRING,
                ['columnDefinition' => 'char(32) collate latin1_general_ci NOT null default "capture"']
            );
        }
    }
}
