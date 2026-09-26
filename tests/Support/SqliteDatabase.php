<?php

namespace NafeesAhmed\Enapi\Tests\Support;

use NafeesAhmed\Enapi\Database;
use PDO;

/**
 * In-memory SQLite Database for tests, bypassing the mysql-style DSN
 * that Database::connect() builds by default.
 */
class SqliteDatabase extends Database
{
    public function __construct()
    {
        parent::__construct([]);
    }

    protected function connect(): void
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo = $pdo;
    }
}
