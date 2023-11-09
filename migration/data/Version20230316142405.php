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
final class Version20230316142405 extends AbstractMigration
{
    public function __construct($version, $logger)
    {
        parent::__construct($version, $logger);

        $this->platform->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up(Schema $schema): void
    {
        $this->insertPayPalTrackingCarrierData();
    }

    public function down(Schema $schema): void
    {
    }

    protected function insertPayPalTrackingCarrierData(): void
    {
        $this->addSql(file_get_contents(__DIR__ . '/Version20230316142405.sql'));
    }
}
