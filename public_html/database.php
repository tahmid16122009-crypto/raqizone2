<?php
class DB {
    private static ?PDO $pdo = null;

    private static function connect(): PDO {
        if (self::$pdo === null) {
            if (!defined('DB_HOST')) require_once __DIR__ . '/config.php';
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = self::connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Real error log-এ যায়, কিন্তু browser-কে generic message দেখায় — SQL structure leak হয় না
            error_log('[DB ERROR] ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new RuntimeException('A database error occurred. Please try again later.');
        }
    }

    public static function rows(string $sql, array $params = []): array {
        return self::run($sql, $params)->fetchAll();
    }

    public static function row(string $sql, array $params = []): ?array {
        $r = self::run($sql, $params)->fetch();
        return $r ?: null;
    }

    public static function exec(string $sql, array $params = []): int {
        self::run($sql, $params);
        return (int)self::connect()->lastInsertId();
    }
}