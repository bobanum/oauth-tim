<?php
abstract class Provider {
    static protected $pdo = null;
    static protected $prefix = 'OAUTH';
    static function getPDO() {
        if (static::$pdo) {
            return static::$pdo;
        }
        // $dbPath = realpath($_SERVER['DOCUMENT_ROOT'] . '/../'. static::config('DATABASE_PATH', 'database/db.sqlite'));
        $dbPath = static::config('DATABASE_PATH', 'database/db.sqlite');
        $dbPath = realpath($dbPath) ?: realpath(static::base_path($dbPath));
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }
    static function getStmt($query, $params = []) {
        $db = static::getPDO();
        $stmt = $db->prepare($query);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute($params);
        return $stmt;
    }
    static function gotToken($token) {
        $user = static::getStmt("SELECT * FROM users WHERE token = ?", [$token])->fetch();

        if (!$user) {
            static::JsonResponse(['error' => 'Invalid token'], 403);
        }

        static::JsonResponse([
            'message' => 'Bienvenue, ' . $user['name'],
            'email' => $user['email']
        ]);
    }

    static function gotCode($code) {
        session_start();
        $access_token = static::redeemCode($code);
        if (!$access_token) {
            static::JsonResponse(['error' => 'Invalid code'], 400);
        }

        $user = static::redeemToken($access_token);

        if (static::getUser($user)) {
            static::updateToken($user);
        } else {
            static::createUser($user);
        }

        static::JsonResponse($user);
    }

    static function getCurl($url, $headers = [], $data = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($data) {
            if (is_array($data)) {
                $data = http_build_query($data);
            }
            array_unshift($headers, 'Content-Type: application/x-www-form-urlencoded');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        return $ch;
    }

    static function curlExec($url, $headers = [], $data = null) {
        $ch = static::getCurl($url, $headers, $data);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            static::JsonResponse(['error' => 'Request failed: ' . curl_error($ch)], 400);
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    static function redeemCode($code, $postFields = []) {
        $postFields['code'] = $code;
        return static::curlExec(static::config('token_url'), [], $postFields);
    }
    abstract static function redeemToken($token);
    static function login() {
        $params = [
            'client_id' => static::config('client_id'),
            'response_type' => 'code',
            'redirect_uri' => static::config('redirect_uri'),
            'response_mode' => 'query',
            'scope' => 'User.Read',
        ];
        $url = static::config('authorize_url') . '?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }

    static function logout() {
        session_start();
        session_destroy();
        static::JsonResponse(['message' => 'Déconnecté']);
    }

    static function generateToken() {
        return bin2hex(random_bytes(32));
    }

    static function getUser($user) {
        if ($user['email']) {
            return static::getUserByEmail($user['email']);
        }
        if ($user['login']) {
            return static::getUserByLogin($user['login']);
        }
    }

    static function getUserByLogin($login) {
        return static::getStmt("SELECT * FROM users WHERE login = ?", [$login])->fetch();
    }

    static function getUserByEmail($email) {
        return static::getStmt("SELECT * FROM users WHERE email = ?", [$email])->fetch();
    }

    static function createUser($user) {
        return static::getStmt("INSERT INTO users (email, first_name, last_name, job_title, token) VALUES (?, ?, ?, ?, ?)", array_values($user));
    }

    static function updateToken($user) {
        return static::updateUserToken($user['email'], $user['token']);
    }
    static function updateUserToken($email, $token) {
        return static::getStmt("UPDATE users SET token = ?, updated_at=current_timestamp WHERE email = ?", [$token, $email]);
    }

    static function JsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    static function config($var, $default = null) {
        $envVar = strtoupper(static::$prefix . '_' . $var);
        if (isset($_ENV[$envVar])) {
            return $_ENV[$envVar];
        }
        $envVar = strtoupper($var);
        if (isset($_ENV[$envVar])) {
            return $_ENV[$envVar];
        }
        return $default;
    }
    static function app_path($file = null) {
        $result = $_SERVER['DOCUMENT_ROOT'];
        if ($file) {
            return $result . '/' . $file;
        }
        return $result;
    }
    static function base_path($file = null) {
        $result = dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
        if ($file) {
            return $result . '/' . $file;
        }
        return $result;
    }
    static function init() {
        Dotenv\Dotenv::createImmutable(static::base_path())->load();
    }
}
// Initialize the OAuth class
Provider::init();
