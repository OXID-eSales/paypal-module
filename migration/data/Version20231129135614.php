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
final class Version20231129135614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->updateUserTable($schema);
    }

    public function down(Schema $schema): void
    {
    }

    protected function updateUserTable(Schema $schema)
    {
        // Check if table exists first
        if (!$schema->hasTable('oxuser')) {
            return;
        }

        $user = $schema->getTable('oxuser');

        // Add columns using raw SQL to avoid type issues
        if (!$user->hasColumn('OSCPAYPALVAULTSETUPTOKEN')) {
            $this->addSql('ALTER TABLE oxuser ADD COLUMN OSCPAYPALVAULTSETUPTOKEN CHAR(32) COLLATE latin1_general_ci DEFAULT NULL');
        }

        if (!$user->hasColumn('OSCPAYPALCUSTOMERID')) {
            $this->addSql('ALTER TABLE oxuser ADD COLUMN OSCPAYPALCUSTOMERID CHAR(32) COLLATE latin1_general_ci DEFAULT NULL COMMENT "PayPal Customer ID used for Vaulting"');
        }
    }
}
