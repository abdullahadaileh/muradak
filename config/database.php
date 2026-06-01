<?php
// ============================================================
//  Muradak Market — Database Configuration
//  Railway Production Environment
// ============================================================

define('DB_HOST',     'zephyr.proxy.rlwy.net');
define('DB_PORT',     '19459');
define('DB_NAME',     'railway');
define('DB_USER',     'root');
define('DB_PASS',     'lWyiiXJSwCmwcmmvsIGEIuRvMFtkkCDr');
define('DB_CHARSET',  'utf8mb4');

define('STORE_EMAIL', 'abdullahadaileh957@gmail.com');
define('STORE_NAME',  'Muradak Market');
define('BASE_URL',    '');             // e.g. https://yourdomain.com

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
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