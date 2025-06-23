<?php
abstract class Provider {
    use ProviderDBTrait;
    static protected $prefix = 'OAUTH';
    static function gotToken($token) {
        $user = static::getStmt("SELECT * FROM user WHERE token = ?", [$token])->fetch();

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

        $user['user_id'] = static::getOrCreateUserId($user);
        $user['token'] = static::generateToken();
        var_dump(__LINE__, $user); // Debugging line, can be removed later
        static::updateToken($user);
        static::JsonResponse($user);
    }

    static function getCurl($url, $headers = [], $data = null) {
        $headers[] = 'Accept: application/json';
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
        // var_dump(__LINE__, $response);die; // Debugging line, can be removed later
        return json_decode($response, true);
    }

    static function redeemCode($code) {
        $postFields = static::tokenData($code);
        $response = static::curlExec(static::config('token_url'), [], $postFields);
        if (!$response || !isset($response['access_token'])) {
            static::JsonResponse(['error' => 'Invalid response from token endpoint'], 400);
        }
        return $response['access_token'];
    }
    abstract static function redeemToken($token);
    abstract static function tokenData($code);
    abstract static function loginParams();

    static function login() {
        $url = static::config('authorize_url') . '?' . http_build_query(static::loginParams());
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
