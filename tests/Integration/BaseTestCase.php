<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration;

use Codeception\Util\Fixtures;
use Exception;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use PDOException;
use PHPUnit\Framework\TestCase;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\Shop;
use Psr\Log\LoggerInterface;

abstract class BaseTestCase extends TestCase
{
    use ServiceContainer;

    protected string $dumpPath = __DIR__ . '/../../tests/_data/dump.sql';
    private QueryBuilderFactoryInterface $queryBuilderFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $dotenv = new \Symfony\Component\Dotenv\Dotenv();
        $dotenv->load(__DIR__ . '/../../tests/.env');

        $this->updateModuleConfiguration('oscPayPalSandboxClientId', $_ENV['oscPayPalSandboxClientId']);
        $this->updateModuleConfiguration('oscPayPalSandboxMode', true);
        $this->updateModuleConfiguration('oscPayPalSandboxClientSecret', $_ENV['oscPayPalSandboxClientSecret']);
        $this->queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
        $this->importTestProducts();
        $this->updateViews();
    }

    /**
     * Cleans up table
     *
     * @param string $table      Table name
     * @param string $columnName Column name
     */
    protected function cleanUpTable($table, $columnName = null)
    {
        $sCol = (!empty($columnName)) ? $columnName : 'oxid';

        //deletes allrecords where oxid or specified column name values starts with underscore(_)
        $sQ = "delete from `$table` where `$sCol` like '\_%' ";

        DatabaseProvider::getDB()->execute($sQ);
    }

    protected function getPsrLoggerMock(): LoggerInterface
    {
        $psrLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'emergency',
                    'alert',
                    'critical',
                    'error',
                    'warning',
                    'notice',
                    'info',
                    'debug',
                    'log'
                ]
            )
            ->getMock();

        return $psrLogger;
    }

    protected function updateModuleConfiguration(string $confName, $value): void
    {
        $this->getServiceFromContainer(ModuleSettings::class)->save($confName, $value);
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    protected function doAssertStringNotContainsString($needle, $haystack, $message = '')
    {
        if (method_exists($this, 'assertStringNotContainsString')) {
            parent::assertStringNotContainsString($needle, $haystack, $message);
        } else {
            parent::assertNotContains($needle, $haystack, $message);
        }
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    protected function doAssertStringContainsString($needle, $haystack, $message = '')
    {
        if (method_exists($this, 'assertStringContainsString')) {
            parent::assertStringContainsString($needle, $haystack, $message);
        } else {
            parent::assertContains($needle, $haystack, $message);
        }
    }

    private function importTestProducts(): void
    {
        $testsDir = realpath(__DIR__ . '/../../tests');
        if ($testsDir === false) {
            throw new Exception("Unable to locate the tests directory.");
        }

        $dumpFilePath = $testsDir . '/_data/dump.sql';
        if (!file_exists($dumpFilePath)) {
            throw new Exception("SQL dump file not found at: {$dumpFilePath}");
        }

        $sqlContent = file_get_contents($dumpFilePath);
        if ($sqlContent === false) {
            throw new Exception("Error reading SQL dump file from: {$dumpFilePath}");
        }

        $sqlStatements = $this->splitSqlStatements($sqlContent);
        $queryBuilder = $this->queryBuilderFactory->create();
        $connection = $queryBuilder->getConnection();

        foreach ($sqlStatements as $statement) {
            if (!empty($statement)) {
                if (stripos($statement, 'OXPIXIEXPORT') !== false) {
                    continue;
                }
                try {
                    $connection->executeStatement($statement);
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        continue;
                    }
                    echo "Error during SQL dump import: " . $e->getMessage();
                }
            }
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $currentStatement = '';
        $inString = false;
        $stringChar = '';
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if ($inString) {
                if ($char === $stringChar) {
                    if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                        $currentStatement .= $char . $sql[$i + 1];
                        $i++;
                        continue;
                    } else {
                        $inString = false;
                    }
                }
                $currentStatement .= $char;
            } else {
                if ($char === "'" || $char === '"') {
                    $inString = true;
                    $stringChar = $char;
                    $currentStatement .= $char;
                } elseif ($char === ';') {
                    $trimmed = trim($currentStatement);
                    if (!empty($trimmed)) {
                        // Remove unwanted fields from the statement.
                        $trimmed = $this->removeUnwantedFields($trimmed, ['OXPIXIEXPORT']);
                        $statements[] = $trimmed;
                    }
                    $currentStatement = '';
                } else {
                    $currentStatement .= $char;
                }
            }
        }

        if (trim($currentStatement) !== '') {
            $trimmed = trim($currentStatement);
            $trimmed = $this->removeUnwantedFields($trimmed, ['OXPIXIEXPORT']);
            $statements[] = $trimmed;
        }

        return $statements;
    }

    private function removeUnwantedFields(string $statement, array $fieldsToRemove): string
    {
        // Match statements that follow the INSERT/REPLACE INTO ... (columns) VALUES (values) pattern.
        if (
            preg_match(
                '/^(REPLACE INTO\s+`?[\w]+`?\s*)\((.*?)\)(\s*VALUES\s*)\((.*?)\)(.*)$/is',
                $statement,
                $matches
            )
        ) {
            $prefix       = $matches[1]; // e.g., "REPLACE INTO `oxorder` "
            $columnsPart  = $matches[2]; // the list of columns
            $valuesSep    = $matches[3]; // the " VALUES " keyword (including surrounding whitespace)
            $valuesPart   = $matches[4]; // the list of values
            $suffix       = $matches[5]; // anything after the values

            // Explode columns and values by comma.
            $columns = array_map('trim', explode(',', $columnsPart));
            $values  = array_map('trim', explode(',', $valuesPart));

            // Iterate over each unwanted field.
            foreach ($fieldsToRemove as $field) {
                // Loop through the columns to find a match.
                foreach ($columns as $index => $column) {
                    // Remove any backticks around the column name.
                    $cleanColumn = trim($column, "`");
                    if (strcasecmp($cleanColumn, $field) === 0) {
                        // Remove the column and the corresponding value.
                        unset($columns[$index]);
                        unset($values[$index]);
                        break; // Assumes the field appears only once.
                    }
                }
            }
            // Reassemble the column and value lists.
            $newColumns = implode(', ', array_values($columns));
            $newValues  = implode(', ', array_values($values));

            // Rebuild the SQL statement without the unwanted field and its value.
            $statement = $prefix . '(' . $newColumns . ')' . $valuesSep . '(' . $newValues . ')' . $suffix;
        }
        return $statement;
    }


    private function updateViews(): void
    {
        $db = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $tables = [
            'oxarticles',
            'oxartextends',
            'oxcategories',
            'oxcountry',
            'oxmanufacturers',
            'oxvendor',
            'oxattribute',
            'oxselectlist',
            'oxstates',
            'oxorder',
            'oxuser'
        ];
        $suffixes = ['_1', ''];

        foreach ($suffixes as $suffix) {
            foreach ($tables as $table) {
                $viewTableName = "oxv_{$table}{$suffix}_de";
                $sql = "CREATE OR REPLACE VIEW {$viewTableName} AS SELECT * FROM {$table}";
                try {
                    $db->execute($sql);
                } catch (\Exception $e) {
                    // Silent fail for view creation
                }
            }
        }
    }
}
