<?php
trait ProviderDBTrait {
    static protected $pdo = null;
    static function getPDO() {
        if (static::$pdo) {
            return static::$pdo;
        }
        // $dbPath = realpath($_SERVER['DOCUMENT_ROOT'] . '/../'. static::config('DATABASE_PATH', 'database/db.sqlite'));
        $dbPath = static::config('DATABASE_PATH', 'database/db.sqlite');
        $dbPath = realpath($dbPath) ?: realpath(static::base_path($dbPath));
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        static::$pdo = $db;
        return $db;
    }
    static function getStmt($query, $params = []) {
        $db = static::getPDO();
        $stmt = $db->prepare($query);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute($params);
        return $stmt;
    }
    static function getStmtInsert9($table, $data) {
        $query = static::sql_insert($table, $data);
        $db = static::getPDO();
        $stmt = $db->prepare($query);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute($data);
        var_dump(__LINE__, $query, $db->lastInsertId()); // Debugging line, can be removed later
        return $db->lastInsertId();
    }
    static function getStmtInsert($table, $data) {
        $sql = static::sql_insert($table, $data);
        static::getStmt($sql, $data);
        return static::getPDO()->lastInsertId();
    }

    static function getUserId($user) {
        $SQL[] = "SELECT id FROM user";
        $SQL[] = "WHERE login = ?";
        $SQL[] = "AND provider_id = ?";
        $SQL = implode(' ', $SQL);
        $data = [
            $user['login'], 
            $user['provider_id'] ?: parent::$prefix
        ];
        $result = static::getStmt($SQL, $data)->fetch();
        if (!$result) {
            return null;
        }
        return $result['id'];
    }
    static function getOrCreateUserId($user) {
        $user_id = static::getUserId($user);
        if ($user_id) {
            return $user_id;
        }
        $user_id = static::createUser($user);
        return $user_id;
    }
    static function sql_insert($table, $data) {
        $columnNames = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($col) => ":$col", array_keys($data)));
        return "INSERT INTO $table ($columnNames) VALUES ($placeholders)";
    }

    static function newUserData($user) {
        $result = [
            'provider_id' => $user['provider_id'] ?: 0,
            'login' => $user['login'],
            'email' => $user['email'] ?? null,
            'name' => $user['name'] ?? null,
            'password' => $user['password'] ?? null,
            'extra' => json_encode($user['response'] ?? []),
        ];
        return $result;
    }
    static function newTokenData($user) {
        $result = [
            'user_id' => $user['user_id'] ?: $user['id'],
            'app_id' => 1, // Assuming a default app_id of 1
            'token' => $user['token'],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')), // Assuming token expires in 1 hour
        ];
        return $result;
    }
    static function createUser($user) {
        $result = static::getStmtInsert('user', static::newUserData($user));
        return $result; // Return the new user ID
    }

    static function updateToken($user) {
        static::getStmtInsert('access_token', static::newTokenData($user));
    }  
}
