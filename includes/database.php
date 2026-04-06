<?php

class Database
{
    public static function getConnection(): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $config = self::loadConfig();

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return $pdo;
    }

    /**
     * @return array{host:string,dbname:string,user:string,pass:string,charset:string}
     */
    private static function loadConfig(): array
    {
        $configPath = __DIR__ . '/../config/database.php';

        if (is_file($configPath)) {
            $config = require $configPath;
            if (is_array($config)) {
                return [
                    'host' => (string) ($config['host'] ?? 'localhost'),
                    'dbname' => (string) ($config['dbname'] ?? ''),
                    'user' => (string) ($config['user'] ?? ''),
                    'pass' => (string) ($config['pass'] ?? ''),
                    'charset' => (string) ($config['charset'] ?? 'utf8mb4'),
                ];
            }
        }

        $fallback = [
            'host' => defined('DB_HOST') ? (string) DB_HOST : ((string) getenv('DB_HOST') ?: 'localhost'),
            'dbname' => defined('DB_NAME') ? (string) DB_NAME : ((string) getenv('DB_NAME') ?: ''),
            'user' => defined('DB_USER') ? (string) DB_USER : ((string) getenv('DB_USER') ?: ''),
            'pass' => defined('DB_PASS') ? (string) DB_PASS : ((string) getenv('DB_PASS') ?: ''),
            'charset' => defined('DB_CHARSET') ? (string) DB_CHARSET : ((string) getenv('DB_CHARSET') ?: 'utf8mb4'),
        ];

        if ($fallback['dbname'] === '' || $fallback['user'] === '') {
            throw new RuntimeException(
                'Configuration DB manquante: créez config/database.php ou définissez DB_NAME et DB_USER.'
            );
        }

        return $fallback;
    }
}
