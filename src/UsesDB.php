<?php

namespace Auth;

use PDO;

trait UsesDB {
    static protected $pdo = null;
    static function getPDO() {
        if (self::$pdo) {
            return self::$pdo;
        }
        $dbPath = $_ENV['AUTH_DB_NAME'];
        $dbPath = realpath($dbPath);
        if (!$dbPath) {
            throw new \Exception("Database file not found: $dbPath");
        }
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // $db->setAttribute(PDO::FETCH_PROPS_LATE, true);
        
        self::$pdo = $db;
        return $db;
    }
    static function execQuery($query, $params = []) {
        $stmt = self::getStmt($query);
        $stmt->execute($params);
        return $stmt;
    }
    static function getStmt($query) {
        $db = self::getPDO();
        $stmt = $db->prepare($query);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt;
    }
    static function insert($table, $data) {
        $query = self::sql_insert($table, $data);
        self::execQuery($query, $data);
        return self::getPDO()->lastInsertId();
    }

    static function sql_insert($table, $data) {
        $columnNames = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($col) => ":$col", array_keys($data)));
        return "INSERT INTO $table ($columnNames) VALUES ($placeholders)";
    }
}
