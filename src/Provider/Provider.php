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
        if (!$access_token) {
            throw new \Exception("Invalid code received from provider");
        }

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
        $response = $this->curlExec($this->token_url, [], $postFields);
        if (!$response || !isset($response['access_token'])) {
            throw new \Exception("Invalid response from token endpoint");
        }
        return $response['access_token'];
    }
    abstract function redeemToken($token);
    abstract function tokenData($code);
    abstract function loginParams();

    function login() {
        $url = $this->authorize_url . '?' . http_build_query($this->loginParams());
        header('Location: ' . $url);
        exit;
    }
    function logout() {
        session_start();
        session_destroy();
        return ['message' => 'Déconnecté'];
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
}
