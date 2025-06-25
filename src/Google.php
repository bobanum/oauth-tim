<?php
class Google extends Provider {
    protected $pdo = null;
    protected $prefix = 'GOOGLE';
    protected $provider_id = 2;

    function __construct() {
        $this->client_id = $this->config('client_id');
        $this->client_secret = $this->config('client_secret');
        $this->scope = $this->config('scope', 'email profile');
        $this->redirect_uri = $this->config('redirect_uri');
        $this->authorize_url = "https://accounts.google.com/o/oauth2/v2/auth";
        $this->token_url = "https://oauth2.googleapis.com/token";
        $this->user_info_url = "https://www.googleapis.com/oauth2/v3/userinfo";
    }

    function tokenData($code) {
        $result = [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'code'          => $code,
            'redirect_uri'  => $this->redirect_uri,
            'grant_type' => 'authorization_code',
        ];
        return $result;
    }
    function redeemToken($token) {
        $data = self::curlExec($this->user_info_url, [
            "Authorization: Bearer $token",
            // "User-Agent: MyApp",
        ]);
        // var_dump(__LINE__, $token, $data); // Debugging line
        if (empty($data['sub']) && empty($data['email'])) {
            $this->JsonResponse(['error' => 'User info fetch failed'], 400);
        }

        return [
            'provider_id' => $this->provider_id,
            'login' => $data['sub'] ?: $data['email'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'response' => $data,
        ];
    }
    function loginParams() {
        $result = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => $this->scope,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        return $result;
    }
}
