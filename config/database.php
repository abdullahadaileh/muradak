<?php
// ============================================================
//  Muradak Market — Database Configuration
//  Change these values to match your hosting environment
// ============================================================

define('DB_HOST',     'localhost');
define('DB_NAME',     'muradak_db');
define('DB_USER',     'root');          // Change on production
define('DB_PASS',     '');             // Change on production
define('DB_CHARSET',  'utf8mb4');

define('STORE_EMAIL', 'abdullahadaileh957@gmail.com');
define('STORE_NAME',  'Muradak Market');
define('BASE_URL',    '');             // e.g. https://yourdomain.com

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
