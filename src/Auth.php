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
            return $this->provider->logout();
        }
        if (isset($_GET['code'])) {
            $user = $this->processCode($_GET['code']);
            return $this->JsonResponse($user);
        }
        if (isset($_GET['token'])) {
            return $this->provider->processToken($_GET['token']);
        }
        if (isset($_GET['app_key'])) {
            $app_key = $_GET['app_key'] ?? null;
            $app = $this->findAppByKey($app_key);
            if (!$app->is_active === 1) {
                die('App not active');
            }
            $_SESSION['referer'] = $_SERVER['HTTP_REFERER'] ?? null;
            $_SESSION['app_key'] = $app_key;
        }
        return $this->provider->login();
    }
    public function processCode($code) {
        try {
            $user = $this->provider->processCode($code);
        } catch (\Exception $e) {
            $this->JsonResponse(['error' => $e->getMessage()], 400);
        }

        $user['user_id'] = $this->getOrCreateUserId($user);
        $user['token'] = $this->generateToken();
        $_SESSION['login'] = $user->id; // Store user in session
        setcookie('token', $user['token'], time() + 60 * 60 * 24 * 30, '/', '', false, true); // Secure and HttpOnly
        $_SESSION['token'] = $user['token']; // Store token in session
        $this->updateToken($user);
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
