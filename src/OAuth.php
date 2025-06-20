<?php

class OAuth {
    static private $pdo = null;
    static $config = [
        'client_id' => 'OAUTH_CLIENT_ID',
        'client_secret' => 'OAUTH_CLIENT_SECRET',
        'redirect_uri' => 'OAUTH_REDIRECT_URI',
        'tenant' => 'OAUTH_TENANT',
        'authorize_url' => 'OAUTH_AUTHORIZE_URL',
        'token_url' => 'OAUTH_TOKEN_URL',
        'user_info_url' => 'OAUTH_USER_INFO_URL',
    ];
    static function getPDO() {
        if (self::$pdo) {
            return self::$pdo;
        }
        $db = new PDO('sqlite:' . __DIR__ . '/../database/db.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }
    static function getStmt($query, $params = []) {
        $db = self::getPDO();
        $stmt = $db->prepare($query);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute($params);
        return $stmt;
    }
    static function gotToken($token) {
        $user = self::getStmt("SELECT * FROM users WHERE token = ?", [$token])->fetch();

        if (!$user) {
            self::JsonResponse(['error' => 'Invalid token'], 403);
        }

        self::JsonResponse([
            'message' => 'Bienvenue, ' . $user['name'],
            'email' => $user['email']
        ]);
    }

    static function gotCode($code) {
        session_start();
        $access_token = self::redeemCode($code);
        if (!$access_token) {
            self::JsonResponse(['error' => 'Invalid code'], 400);
        }

        $data = self::redeemToken($access_token);

        if (empty($data['userPrincipalName'])) {
            self::JsonResponse(['error' => 'User info fetch failed'], 400);
        }

        $user = [
            'email' => $data['userPrincipalName'],
            'first_name' => $data['givenName'] ?? null,
            'last_name' => $data['surname'] ?? null,
            'job_title' => $data['jobTitle'] ?? null,
            'token' => self::generateToken(),
        ];

        if (self::getUserByEmail($user['email'])) {
            self::updateUserToken($user['email'], $user['token']);
        } else {
            self::createUser($user);
        }

        self::JsonResponse($user);
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

    static function redeemCode($code) {
        $postFields = [
            'client_id'     => self::config('client_id'),
            'scope'         => 'User.Read',
            'code'          => $code,
            'redirect_uri'  => self::config('redirect_uri'),
            'grant_type'    => 'authorization_code',
            'client_secret' => self::config('client_secret'),
        ];
        $ch = self::getCurl(self::config('token_url'), [], $postFields);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            self::JsonResponse(['error' => 'Token fetch failed: ' . curl_error($ch)], 400);
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $tokenResponse = json_decode($response, true);
        return $tokenResponse['access_token'] ?? null;
    }
    static function redeemToken($token) {
        $ch = self::getCurl(self::config('user_info_url'), [
            "Authorization: Bearer $token"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            self::JsonResponse(['error' => 'User info fetch failed: ' . curl_error($ch)], 400);
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    static function login() {
        $params = [
            'client_id' => self::config('client_id'),
            'response_type' => 'code',
            'redirect_uri' => self::config('redirect_uri'),
            'response_mode' => 'query',
            'scope' => 'User.Read',
        ];
        $url = self::config('authorize_url') . '?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }

    static function logout() {
        session_start();
        session_destroy();
        self::JsonResponse(['message' => 'Déconnecté']);
    }

    static function generateToken() {
        return bin2hex(random_bytes(32));
    }

    static function getUserByEmail($email) {
        return self::getStmt("SELECT * FROM users WHERE email = ?", [$email])->fetch();
    }

    static function createUser($user) {
        return self::getStmt("INSERT INTO users (email, first_name, last_name, job_title, token) VALUES (?, ?, ?, ?, ?)", array_values($user));
    }

    static function updateUserToken($email, $token) {
        return self::getStmt("UPDATE users SET token = ?, updated_at=current_timestamp WHERE email = ?", [$token, $email]);
    }

    static function JsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    static function config($var) {
        if (!isset(self::$config[$var])) {
            return $var;
        }
        $envVar = self::$config[$var];
        if (isset($_ENV[$envVar])) {
            return $_ENV[$envVar];
        }
        return null;
    }
    static function init() {
        Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
    }
}
// Initialize the OAuth class
OAuth::init();
