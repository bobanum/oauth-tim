<?php

namespace Auth;

use Dotenv\Dotenv;

Dotenv::createImmutable(dirname(__DIR__))->load();

class Auth {
    use DBTrait;
    protected $provider;

    public function __construct($provider) {
        if (is_string($provider)) {
            $provider = "Auth\\Provider\\$provider"; // Namespaced class
            $provider = new $provider();
        } elseif (!is_object($provider)) {
            throw new \Exception("Invalid provider type. Must be a string or an object.");
        }
        $this->provider = $provider;
    }

    public function handleRequest() {
        if (isset($_GET['logout'])) {
            $this->provider->logout();
        } elseif (isset($_GET['code'])) {
            $this->processCode($_GET['code']);
        } elseif (isset($_GET['token'])) {
            $this->provider->processToken($_GET['token']);
        } else {
            $this->provider->login();
        }
    }
    public function processCode($code) {
        try {
            $user = $this->provider->processCode($code);
        } catch (\Exception $e) {
            $this->JsonResponse(['error' => $e->getMessage()], 400);
        }

        $user['user_id'] = $this->getOrCreateUserId($user);
        $user['token'] = $this->generateToken();
        setcookie('token', $user['token'], time() + 60 * 60 * 24 * 30, '/', '', false, true); // Secure and HttpOnly
        $this->updateToken($user);
        $this->JsonResponse($user);
        return $user;
    }
    static function JsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    function generateToken() {
        return bin2hex(random_bytes(32));
    }

    function isLoggedIn() {
        var_dump(__LINE__, $_COOKIE);
        die; // Debugging line
        if (isset($_COOKIE['token'])) {
            return true;
        }
        session_start();
        return isset($_SESSION['user']);
    }
    function base_path($file = null) {
        $result = dirname(__DIR__);
        if ($file) {
            return $result . '/' . $file;
        }
        return $result;
    }
}
