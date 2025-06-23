<?php
class Azure extends Provider {
    static protected $prefix = 'AZURE';
    static protected $provider_id = 1;
    static function tokenData($code) {
        $result = [
            'client_id'     => static::config('client_id'),
            'scope'         => 'User.Read',
            'redirect_uri'  => static::config('redirect_uri'),
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_secret' => static::config('client_secret'),
        ];
        return $result;
    }
    static function redeemCode($code, $postFields = []) {
        $postFields = static::tokenData($code);
        $tokenResponse = parent::redeemCode($code, $postFields);
        return $tokenResponse['access_token'] ?? null;
    }
    static function redeemToken($token) {
        $data = self::curlExec(static::config('user_info_url'), [
            "Authorization: Bearer $token"
        ]);
        if (empty($data['userPrincipalName'])) {
            static::JsonResponse(['error' => 'User info fetch failed'], 400);
        }
        return [
            'provider_id' => static::$provider_id,
            'login' => $data['userPrincipalName'],
            'email' => $data['userPrincipalName'],
            'name' => $data['displayName'] ?? null,
            'response' => $data,
        ];
    }
    static function loginParams() {
        $result = [
            'client_id' => static::config('client_id'),
            'response_type' => 'code',
            'redirect_uri' => static::config('redirect_uri'),
            'response_mode' => 'query',
            'scope' => 'User.Read',
        ];
        return $result;
    }
}