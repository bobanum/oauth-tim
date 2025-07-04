<?php
namespace Auth;
use PDO;

trait DBTrait {
    protected $pdo = null;
    function getPDO() {
        if ($this->pdo) {
            return $this->pdo;
        }
        // $dbPath = $this->config('DB_NAME', 'database/db.sqlite');
        $dbPath = $_ENV['DB_NAME'] ?? 'database/db.sqlite';
        $dbPath = realpath($dbPath) ?: realpath($this->base_path($dbPath));
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $this->pdo = $db;
        return $db;
    }
    function getStmt($query, $params = []) {
        $db = $this->getPDO();
        $stmt = $db->prepare($query);
        // $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute($params);
        return $stmt;
    }
    function getStmtInsert($table, $data) {
        $sql = $this->sql_insert($table, $data);
        $this->getStmt($sql, $data);
        return $this->getPDO()->lastInsertId();
    }

    function getUserId($user) {
        $SQL[] = "SELECT id FROM user";
        $SQL[] = "WHERE login = ?";
        $SQL[] = "AND provider_id = ?";
        $SQL = implode(' ', $SQL);
        $data = [
            $user['login'], 
            $user['provider_id'] ?: parent::$prefix
        ];
        $result = $this->getStmt($SQL, $data)->fetch();
        if (!$result) {
            return null;
        }
        return $result['id'];
    }
    function getOrCreateUserId($user) {
        $user_id = $this->getUserId($user);
        if ($user_id) {
            return $user_id;
        }
        $user_id = $this->createUser($user);
        return $user_id;
    }
    function sql_insert($table, $data) {
        $columnNames = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($col) => ":$col", array_keys($data)));
        return "INSERT INTO $table ($columnNames) VALUES ($placeholders)";
    }

    function newUserData($user) {
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
    function newTokenData($user) {
        $result = [
            'user_id' => $user['user_id'] ?: $user['id'],
            'app_id' => 1, // Assuming a default app_id of 1
            'token' => $user['token'],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')), // Assuming token expires in 1 hour
        ];
        return $result;
    }
    function createUser($user) {
        $result = $this->getStmtInsert('user', $this->newUserData($user));
        return $result; // Return the new user ID
    }

    function updateToken($user) {
        $this->getStmtInsert('access_token', $this->newTokenData($user));
    }
    function findAppByKey($app_key) {
        $SQL = "SELECT * FROM app WHERE app_key = ?";
        $stmt = $this->getStmt($SQL, [$app_key]);
        $result = $stmt->fetch();
        if (!$result) {
            throw new \Exception("App not found for key: $app_key");
        }
        if (empty($result->id)) {
            throw new \Exception("App ID is empty for key: $app_key");
        }
        return $result;
    }
}
