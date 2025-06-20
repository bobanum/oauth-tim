<?php
class Azure extends Provider {
    static $prefix = 'AZURE';
    static function redeemCode($code, $postFields = []) {
        $postFields = [
            'client_id'     => static::config('client_id'),
            'scope'         => 'User.Read',
            'redirect_uri'  => static::config('redirect_uri'),
            'grant_type'    => 'authorization_code',
            'client_secret' => static::config('client_secret'),
        ];
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
            'login' => $data['userPrincipalName'],
            'email' => $data['userPrincipalName'],
            'name' => $data['displayName'] ?? null,
            'token' => static::generateToken(),
            'response' => $data,
        ];
    }

}