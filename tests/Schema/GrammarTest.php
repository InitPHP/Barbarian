<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Schema;

use InitPHP\Barbarian\Schema\Grammar;
use PDO;
use PHPUnit\Framework\TestCase;

final class GrammarTest extends TestCase
{
    public function testQuoteIdentifierForMysql(): void
    {
        $this->assertSame('`versions`', (new Grammar('mysql'))->quoteIdentifier('versions'));
    }

    public function testQuoteIdentifierForSqliteAndPgsql(): void
    {
        $this->assertSame('"versions"', (new Grammar('sqlite'))->quoteIdentifier('versions'));
        $this->assertSame('"versions"', (new Grammar('pgsql'))->quoteIdentifier('versions'));
    }

    public function testQuoteIdentifierFallsBackToBackticks(): void
    {
        $this->assertSame('`versions`', (new Grammar('unknown'))->quoteIdentifier('versions'));
    }

    public function testMysqlVersionTableUsesAutoIncrement(): void
    {
        $sql = (new Grammar('mysql'))->createVersionTable('migrations_versions');

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `migrations_versions`', $sql);
        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
    }

    public function testPgsqlVersionTableIsValid(): void
    {
        // Regression for the broken PostgreSQL DDL: it must use SERIAL for the
        // primary key and must not contain the bogus FOREIGN KEY clause.
        $sql = (new Grammar('pgsql'))->createVersionTable('migrations_versions');

        $this->assertStringContainsString('SERIAL PRIMARY KEY', $sql);
        $this->assertStringNotContainsString('FOREIGN KEY', $sql);
    }

    public function testSqliteVersionTableExecutes(): void
    {
        $sql = (new Grammar('sqlite'))->createVersionTable('migrations_versions');
        $this->assertStringContainsString('AUTOINCREMENT', $sql);

        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec($sql);

        $exists = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='migrations_versions'",
        );
        $this->assertNotFalse($exists);
        $this->assertSame('migrations_versions', $exists->fetchColumn());
    }
}
