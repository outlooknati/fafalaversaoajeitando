<?php
const DB_HOST  = 'localhost';
const DB_NOME  = 'fafala';
const DB_USER  = 'root';
const DB_SENHA = '';

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;

function conectar(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NOME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_SENHA, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
