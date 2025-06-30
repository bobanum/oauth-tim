<?php
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
abstract class Provider {
    use ProviderDBTrait;
    protected $prefix = 'OAUTH';
    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;
    protected $scope;
    protected $token_url;
    protected $authorize_url;
    protected $user_info_url;
    function gotToken($token) {
        $user = $this->getStmt("SELECT * FROM user WHERE token = ?", [$token])->fetch();

        if (!$user) {
            $this->JsonResponse(['error' => 'Invalid token'], 403);
        }

        $this->JsonResponse([
            'message' => 'Bienvenue, ' . $user['name'],
            'email' => $user['email']
        ]);
    }

    function gotCode($code) {
        session_start();
        $access_token = $this->redeemCode($code);
        if (!$access_token) {
            $this->JsonResponse(['error' => 'Invalid code'], 400);
        }

        $user = $this->redeemToken($access_token);

        $user['user_id'] = $this->getOrCreateUserId($user);
        $user['token'] = $this->generateToken();
        setcookie('token', $user['token'], time() + 60 * 60 * 24 * 30, '/', '', false, true); // Secure and HttpOnly
        $this->updateToken($user);
        $this->JsonResponse($user);
    }

    function getCurl($url, $headers = [], $data = null) {
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

    function curlExec($url, $headers = [], $data = null) {
        $ch = $this->getCurl($url, $headers, $data);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->JsonResponse(['error' => 'Request failed: ' . curl_error($ch)], 400);
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    function redeemCode($code) {
        $postFields = $this->tokenData($code);
        $response = $this->curlExec($this->token_url, [], $postFields);
        if (!$response || !isset($response['access_token'])) {
            $this->JsonResponse(['error' => 'Invalid response from token endpoint'], 400);
        }
        return $response['access_token'];
    }
    abstract function redeemToken($token);
    abstract function tokenData($code);
    abstract function loginParams();

    function login() {
        $url = $this->authorize_url . '?' . http_build_query($this->loginParams());
        // var_dump(__LINE__,$url);die;
        header('Location: ' . $url);
        exit;
    }
    function logout() {
        session_start();
        session_destroy();
        $this->JsonResponse(['message' => 'Déconnecté']);
    }

    function generateToken() {
        return bin2hex(random_bytes(32));
    }

    function JsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    function config($var, $default = null) {
        $envVar = strtoupper($this->prefix . '_' . $var);
        if (isset($_ENV[$envVar])) {
            return $_ENV[$envVar];
        }
        $envVar = strtoupper($var);
        if (isset($_ENV[$envVar])) {
            return $_ENV[$envVar];
        }
        return $default;
    }
    function app_path($file = null) {
        $result = $_SERVER['DOCUMENT_ROOT'];
        if ($file) {
            return $result . '/' . $file;
        }
        return $result;
    }
    function base_path($file = null) {
        $result = dirname(__DIR__);
        if ($file) {
            return $result . '/' . $file;
        }
        return $result;
    }
}
