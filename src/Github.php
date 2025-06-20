<?php
class Github extends Provider {
    static protected $pdo = null;
    static protected $prefix = 'GITHUB';
    static function gotToken($token) {
        $user = static::getStmt("SELECT * FROM users WHERE token = ?", [$token])->fetch();

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

        if (static::getUser($user)) {
            static::updateToken($user['email'], $user['token']);
        } else {
            static::createUser($user);
        }

        static::JsonResponse($user);
    }

    static function redeemCode($code, $postFields = []) {
        $postFields = [
            'client_id'     => static::config('client_id'),
            'client_secret' => static::config('client_secret'),
            'code'          => $code,
            'redirect_uri'  => static::config('redirect_uri'),
        ];
        $tokenResponse = parent::redeemCode($code, $postFields);
        return $tokenResponse['access_token'] ?? null;
    }
    static function redeemToken($token) {
        $data = self::curlExec(static::config('user_info_url'), [
            "Authorization: token $token",
            "User-Agent: MyApp",
        ]);

        if (empty($data['userPrincipalName'])) {
            static::JsonResponse(['error' => 'User info fetch failed'], 400);
        }

        return [
            'login' => $data['login'] ?? 'unknown',
            'email' => null,
            'name' => $data['name'] ?? null,
            'token' => static::generateToken(),
            'response' => $data,
        ];
    }
    static function login() {
        $params = [
            'client_id' => static::config('client_id'),
            'redirect_uri' => static::config('redirect_uri'),
            'scope' => 'read:user user:email',
            'allow_signup' => 'true',
        ];
        $url = static::config('authorize_url') . '?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }
}
