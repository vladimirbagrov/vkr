<?php
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'cg38360_vkr';
        $user = 'cg38360_vkr';
        $pass = '05122003BagVA';
        $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }
    return $pdo;
}