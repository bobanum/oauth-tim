<?php
class Github extends Provider {
    static protected $pdo = null;
    static protected $prefix = 'GITHUB';
    static protected $provider_id = 4;

    static function tokenData($code) {
        $result = [
            'client_id'     => static::config('client_id'),
            'client_secret' => static::config('client_secret'),
            'code'          => $code,
            'redirect_uri'  => static::config('redirect_uri'),
        ];
        return $result;
    }
    static function redeemToken($token) {
        $data = self::curlExec(static::config('user_info_url'), [
            "Authorization: token $token",
            "User-Agent: MyApp",
        ]);

        // var_dump(__LINE__, $data);die; // Debugging line, can be removed later
        if (empty($data['login']) && empty($data['id'])) {
            static::JsonResponse(['error' => 'User info fetch failed'], 400);
        }

        return [
            'provider_id' => static::$provider_id,
            'login' => $data['login'] ?: $data['id'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            // 'token' => static::generateToken(),
            'response' => $data,
        ];
    }
    static function loginParams() {
        $result = [
            'client_id' => static::config('client_id'),
            'redirect_uri' => static::config('redirect_uri'),
            // 'scope' => 'read:user user:email',
            'allow_signup' => 'true',
        ];
        return $result;
    }
}
