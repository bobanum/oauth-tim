<?php

namespace Auth;

use Auth\Provider\Provider;

class Auth {
    use UsesDB;
    use Trait\Debug;
    static public $default_provider = 'google'; // Default provider if none is specified

    /**
     * Retrieves an authentication provider instance based on the given provider name.
     *
     * @param string $provider The name or identifier of the authentication provider.
     * @return mixed The provider instance corresponding to the specified provider, or null if not found.
     */
    static public function getProvider($provider) {
        if (is_object($provider)) {
            return $provider;
        }
        if (is_string($provider)) {
            $provider = ucfirst(trim($provider));
            $provider = "Auth\\Provider\\{$provider}"; // Namespaced class
            $provider = new $provider();
            return $provider;
        }
        throw new \Exception("Invalid provider type. Must be a string or an object.");
    }

    /**
     * Logs out the current user by clearing authentication data.
     *
     * This static method handles the process of logging out a user,
     * such as destroying session data or removing authentication tokens.
     *
     * @return Response A response object indicating the logout status.
     */
    static function logout(): Response {
        if (!session_id()) {
            session_start();
        }
        session_destroy();
        setcookie('PHPSESSID', '', time() - 3600, '/', '', false, true); // Secure and HttpOnly
        return new Response(['message' => 'Logged out successfully']);
    }

    /**
     * Handles the logout process for the authentication system.
     *
     * This static method should be called to perform all necessary steps to log out a user,
     * such as clearing session data, cookies, or tokens.
     *
     * @return Response A response object indicating the logout status.
     */
    static function handleLogout(): Response {
        if (!isset($_GET['logout'])) return Response::empty();
        return self::logout();
    }

    static public function handleCode(Provider $provider): Response {
        if (!isset($_GET['code'])) return Response::empty();
        $user = self::processCode($provider, $_GET['code']);
        if (!$user) {
            return new Response(['error' => 'Invalid code'], 403);
        }
        // try {
        // } catch (\Exception $e) {
        //     if ($e->getCode() === 400) {
        //         return $provider->loginUrl();
        //     }
        //     return self::JsonResponse(['error' => $e->getMessage()], 400);
        // }
        $location = self::getReferer();
        unset($_SESSION['referer']);
        return Response::redirect($location ?? '/', 302);
        // if (!$location) {
        //     $location = '/'; // Default redirect location if referer is not set
        // }
        // header('Location: ' . $location);
        // die;
    }
    static public function getReferer() {
        if (!empty($_SESSION['referer'])) {
            return $_SESSION['referer'];
        } else {
            $result = [
                ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://',
                $_SERVER['HTTP_HOST'],
                // '?app_key=' . $_SESSION['app_key'] ?? '',
            ];
            return implode('', $result);
        }
    }
    static public function processCode(Provider $provider, $code) {
        $user = new User($provider->processCode($code));
        $token = $user->getToken();
        $user->getOrCreateUser();
        $_SESSION['login'] = $user->login; // Store user in session
        $_SESSION['name'] = $user->name; // Store user name in session
        $_SESSION['token'] = $token; // Store token in session
        setcookie('token', $token, time() + 60 * 60 * 24 * 30, '/', '', false, true); // Secure and HttpOnly
        $user->updateToken();
        return $user;
    }
    static function redirect($location, $status = 302): Response {
        $referer = $_SESSION['referer'] ?? $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer && strpos($_SERVER['HTTP_HOST'], $referer) === false) {
            return new Response(['status' => 'redirect', 'location' => $location, 'code' => $status], 403);
        }
        $_SESSION['referer'] = $referer ?? str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']) ?? '/';
        // header('Location: ' . $location, true, $status);
        // exit;
        return Response::redirect($location, $status);
    }

    static function isLoggedIn(): bool {
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
    static function base_path($file = null) {
        $result = dirname(__DIR__);
        if ($file) {
            return $result . '/' . $file;
        }
        return $result;
    }
    static function getApp($app_key = null) {
        if ($app_key === null) {
            $app_key = self::getAppKey();
        }
        $app = App::fromKey($app_key);
        return $app;
    }
    static function getAppKey() {
        $app_key = $_GET['app_key'] ?? null;
        if ($app_key) {
            $_SESSION['app_key'] = $app_key; // Store app key in session
            return $app_key;
        }
        $app_key = $_SESSION['app_key'] ?? null;
        return $app_key;
    }
    static public function main(): Response {
        $response = self::handleLogout();
        if (!$response->empty) return $response;

        $app_key = self::getAppKey();
        if (!$app_key) {
            return new Response(['error' => 'App key is required'], 400);
        }
        if (self::isLoggedIn()) return Response::empty();
        $_SESSION['referer'] = $_SESSION['referer'] ?? $_SERVER['HTTP_REFERER'] ?? null;
        $app = App::fromKey($app_key);
        if (!$app) {
            return new Response(['error' => 'Invalid app key: ' . $app_key], 403);
        }
        if (!$app->is_active === 1) {
            return new Response(['error' => 'App not active'], 403);
        }

        $provider = $app->validateProvider($_GET['provider'] ?? self::$default_provider);
        if (!$provider) {
            return new Response(['error' => 'Invalid provider'], 403);
        }
        $response = self::handleCode($provider); // Process the code if present
        // self::vdf($response);
        if (!$response->empty) return $response;
        $_SESSION['app_key'] = $app_key;
        if (!empty($app->databases)) {
            $_SESSION['databases'] = explode('|', $app->databases);
        }
        return $provider->redirect();
    }
}
