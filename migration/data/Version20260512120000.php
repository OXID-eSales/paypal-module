<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $sql = (string)file_get_contents(__DIR__ . '/Version20260512120000.sql');
        // Strip line comments so DELETE and INSERT can be executed as separate statements.
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);

        foreach (preg_split('/;\s*\n/', $sql, -1, PREG_SPLIT_NO_EMPTY) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $this->addSql($statement);
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
