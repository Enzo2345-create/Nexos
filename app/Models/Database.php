<?php
namespace App\Models;
use PDO;

class Database {
    private static ?PDO $pdo = null;
    public static function connect(): PDO {
        $db = require __DIR__ . '/../../config/database.php';
        
        $host = ($db['host'] === 'localhost') ? '127.0.0.1' : $db['host'];
        $port = (int) ($db['port'] ?? 3306);

        return self::$pdo ??= new PDO(
            "mysql:host={$host};port={$port};dbname={$db['name']};charset={$db['charset']}",
            $db['user'],
            $db['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}
