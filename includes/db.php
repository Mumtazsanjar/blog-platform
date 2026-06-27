<?php
/**
 * Database connection using PDO
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'blog_platform');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // In production, log and show a generic error
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('<h1>Service temporarily unavailable.</h1>');
        }
    }
    return $pdo;
}
