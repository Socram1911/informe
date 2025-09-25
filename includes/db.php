<?php
require_once __DIR__ . '/../config/database.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $cfg = db_config();
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['DB_HOST'], $cfg['DB_PORT'], $cfg['DB_NAME']);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $cfg['DB_USER'], $cfg['DB_PASS'], $options);
    // Modo estricto SQL si aplica
    $pdo->exec("SET SESSION sql_mode='STRICT_ALL_TABLES'");
    return $pdo;
}