<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231129135614 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->updateUserTable($schema);
    }

    public function down(Schema $schema) : void
    {
    }

    protected function updateUserTable(Schema $schema)
    {
        $user = $schema->getTable('oxuser');
        if (!$user->hasColumn('OSCPAYPALVAULTSETUPTOKEN')) {
            $user->addColumn(
                'OSCPAYPALVAULTSETUPTOKEN',
                'string',
                ['columnDefinition' => 'char(32) collate latin1_general_ci default NULL']
            );
        }
        if (!$user->hasColumn('OSCPAYPALCUSTOMERID')) {
            $user->addColumn(
                'OSCPAYPALCUSTOMERID',
                'string',
                ['columnDefinition' => 'char(32) collate latin1_general_ci default NULL',
                 'comment' => 'PayPal Customer ID used for Vaulting ']
            );
        }
    }
}
