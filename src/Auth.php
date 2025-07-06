<?php

namespace Auth;

// use Dotenv\Dotenv;

// Dotenv::createImmutable(dirname(__DIR__))->load();

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

    static function logout() {
        if (!session_id()) {
            session_start();
        }
        session_destroy();
        // Clear cookies
        setcookie('token', '', time() - 3600, '/', '', false, true); // Secure and HttpOnly
        return ['message' => 'Déconnecté'];
    }

    static function handleLogout() {
        if (isset($_GET['logout'])) {
            return self::logout();
        }
    }

    public function handleRequest() {
        if (isset($_GET['logout'])) {
            $this->provider->logout();
            return self::logout();
        }
        if (isset($_GET['code'])) {
            try {
                $user = $this->processCode($_GET['code']);
            } catch (\Exception $e) {
                if ($e->getCode() === 400) {
                    return $this->provider->login();
                }
                return $this->JsonResponse(['error' => $e->getMessage()], 400);
            }
            if (!empty($_SESSION['referer'])) {
                $location = $_SESSION['referer'];
            } else {
                $location = [
                    ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://',
                    $_SERVER['HTTP_HOST'],
                    '?app_key=' . $_SESSION['app_key'] ?? '',
                ];
                $location = implode('', $location);
            }
            header('Location: ' . $location);
            die;
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
        $user = $this->provider->processCode($code);

        $user['user_id'] = $this->getOrCreateUserId($user);
        $user['token'] = $this->generateToken();
        $_SESSION['login'] = $user['login']; // Store user in session
        $_SESSION['name'] = $user['name']; // Store user in session
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

    static function isLoggedIn(): bool {
        // var_dump(__LINE__, $_COOKIE);
        // die; // Debugging line
        if (!isset($_COOKIE['token'])) {
            return false;
        }
        if (!isset($_SESSION['token']) || $_SESSION['token'] !== $_COOKIE['token']) {
            // DELETE the cookie if it doesn't match the session token
            setcookie('token', '', time() - 3600, '/', '', false, true); // Secure and HttpOnly
            unset($_SESSION['token']);
            return false;
        }
        return true;
    }
    function base_path($file = null) {
        $result = dirname(__DIR__);
        if ($file) {
            return $result . '/' . $file;
        }
        return $result;
    }
    static function getAppKey() {
        if (isset($_SESSION['app_key'])) {
            if (!isset($_GET['app_key']) || $_GET['app_key'] === $_SESSION['app_key']) {
                return $_SESSION['app_key'];
            }
        }
        if (isset($_GET['app_key'])) {
            $app = self::getAppFromKey($_GET['app_key']);
            if ($app) {
                $_SESSION['app_key'] = $_GET['app_key'];
                return $_GET['app_key'];
            }
            throw new \Exception("Invalid app key: " . $_GET['app_key']);
        }
        throw new \Exception("App key is required");
    }
    static function getAppFromKey($app_key) {
        $pdo = new \PDO('sqlite:../database/oauth.sqlite');
        $sql = "SELECT * FROM app WHERE app_key = ? AND is_active = 1 LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$app_key]);
        $app = $stmt->fetch();

        return $app ?? null;
    }
}
