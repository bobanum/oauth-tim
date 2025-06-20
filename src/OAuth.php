<?php

class OAuth {
    static private $pdo = null;
    static $config = null;
    static function getPDO() {
        if (self::$pdo) {
            return self::$pdo;
        }
        $db = new PDO('sqlite:' . __DIR__ . '../database/db.sqlite');
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
        // $db = self::getPDO();
        // $stmt = $db->prepare("SELECT * FROM users WHERE token = ?");
        // $stmt->execute([$token]);
        // $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
        // var_dump("gotCode", $_SERVER);

        $access_token = self::redeemCode($code);
        if (!$access_token) {
            self::JsonResponse(['error' => 'Invalid code'], 400);
        }

        $user = self::redeemToken($access_token);
        var_dump("redeemToken", $user);
        die;
        $user['token'] = OAuth::generateToken();
        if (OAuth::getUserByEmail($user['email'])) {
            OAuth::updateUserToken($user['email'], $user['token']);
        } else {
            OAuth::createUser($user['email'], $user['name'], $user['token']);
        }

        self::JsonResponse($user);
    }

    static function getCurl($url, $headers = [], $data=null) {
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
            'client_id'     => self::$config['client_id'],
            'scope'         => 'User.Read',
            'code'          => $code,
            'redirect_uri'  => self::$config['redirect_uri'],
            'grant_type'    => 'authorization_code',
            'client_secret' => self::$config['client_secret'],
        ];
        $ch = self::getCurl(self::$config['token_url'], [], $postFields);

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
        $ch = self::getCurl(self::$config['user_info_url'], [
            "Authorization: Bearer $token"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            self::JsonResponse(['error' => 'User info fetch failed: ' . curl_error($ch)], 400);
        }

        curl_close($ch);

        $user = json_decode($response, true);
        $email = $user['userPrincipalName'] ?? null;
        $name = $user['displayName'] ?? null;

        if (!$email || !$name) {
            self::JsonResponse(['error' => 'User info fetch failed'], 400);
        }

        return [
            'email' => $email,
            'name' => $name
        ];
    }

    static function login() {
        $params = [
            'client_id' => self::$config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => self::$config['redirect_uri'],
            'response_mode' => 'query',
            'scope' => 'User.Read',
        ];
        $url = self::$config['authorize_url'] . '?' . http_build_query($params);
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
        $db = self::getPDO();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static function createUser($email, $name, $token) {
        $db = self::getPDO();
        $stmt = $db->prepare("INSERT INTO users (email, name, token) VALUES (?, ?, ?)");
        $stmt->execute([$email, $name, $token]);
    }

    static function updateUserToken($email, $token) {
        $db = self::getPDO();
        $stmt = $db->prepare("UPDATE users SET token = ? WHERE email = ?");
        $stmt->execute([$token, $email]);
    }

    static function JsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
OAuth::$config = require '../config.php';
