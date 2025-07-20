<?php

namespace Auth\Provider;

abstract class Provider {
    protected $prefix = 'OAUTH';
    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;
    protected $scope;
    protected $token_url;
    protected $authorize_url;
    protected $user_info_url;

    function processCode($code) {
        $access_token = $this->redeemCode($code);
        $user = $this->redeemToken($access_token);
        return $user;
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
            curl_close($ch);
            throw new \Exception("Request failed: " . curl_error($ch));
            return null;
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    function redeemCode($code) {
        $postFields = $this->tokenData($code);
        try {
            $response = $this->curlExec($this->token_url, [], $postFields);
        } catch (\Exception $e) {
            throw new \Exception("Invalid token data: " . $e->getMessage());
        }
        if (!$response) {
            throw new \Exception("Invalid response from token endpoint");
        }
        if (isset($response['error']) && $response['error'] === 'invalid_grant') {
            throw new \Exception("Invalid grant", 400);
        }
        if (!isset($response['access_token'])) {
            throw new \Exception("Invalid response from token endpoint");
        }
        return $response['access_token'];
    }
    abstract function redeemToken($token);
    abstract function tokenData($code);
    abstract function loginParams();

    function loginUrl() {
        return $this->authorize_url . '?' . http_build_query($this->loginParams());
    }
    function logout() {
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
    /**
     * Retrieves an authentication provider instance based on the given provider name.
     *
     * @param string $provider The name or identifier of the authentication provider.
     * @return mixed The provider instance corresponding to the specified provider, or null if not found.
     */
    static public function fromName($provider) {
        if (is_object($provider)) {
            return $provider;
        }
        if (is_string($provider)) {
            $provider = __NAMESPACE__ . '\\' . ucfirst(trim($provider));
            return new $provider();
        }
        throw new \Exception("Invalid provider type. Must be a string or an object.");
    }
    function redirect() {
        $location = $this->loginUrl();
        $status = 302;
        $referer = $_SESSION['referer'] ?? $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer && strpos($_SERVER['HTTP_HOST'], $referer) === false) {
            return ['status' => 'redirect', 'location' => $location, 'code' => $status];
        }
        $_SESSION['referer'] = $referer ?? str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']) ?? '/';
        header('Location: ' . $location, true, $status);
        exit;
    }
}
