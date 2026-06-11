<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian;

use InitPHP\Barbarian\QueryRunner;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class QueryRunnerTest extends TestCase
{
    private function pdo(int $errorMode = PDO::ERRMODE_EXCEPTION): PDO
    {
        return new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => $errorMode]);
    }

    public function testDriverReturnsName(): void
    {
        $runner = new QueryRunner($this->pdo());

        $this->assertSame('sqlite', $runner->driver());
    }

    public function testPdoAccessorReturnsConnection(): void
    {
        $pdo = $this->pdo();
        $runner = new QueryRunner($pdo);

        $this->assertSame($pdo, $runner->pdo());
    }

    public function testQueryReturnsExecutedStatement(): void
    {
        $runner = new QueryRunner($this->pdo());
        $runner->query('CREATE TABLE t (id INTEGER PRIMARY KEY, name TEXT)');
        $runner->query('INSERT INTO t (name) VALUES (:name)', [':name' => 'barbarian']);

        $statement = $runner->query('SELECT name FROM t WHERE id = :id', [':id' => 1]);

        $this->assertInstanceOf(PDOStatement::class, $statement);
        $this->assertSame('barbarian', $statement->fetchColumn());
    }

    public function testConstructorForcesExceptionErrorMode(): void
    {
        // Even when the caller hands over a silent connection, the runner must
        // switch it to exception mode so failures are never swallowed.
        $runner = new QueryRunner($this->pdo(PDO::ERRMODE_SILENT));

        $this->expectException(PDOException::class);
        $runner->query('SELECT * FROM table_that_does_not_exist');
    }
}
