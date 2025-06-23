<?php
class Github extends Provider {
    protected $pdo = null;
    protected $prefix = 'GITHUB';
    protected $provider_id = 4;
    
    function __construct() {
        $this->client_id = $this->config('client_id');
        $this->client_secret = $this->config('client_secret');
        $this->scope = $this->config('scope', 'read:user user:email');
        $this->redirect_uri = $this->config('redirect_uri');
        $this->authorize_url = "https://github.com/login/oauth/authorize";
        $this->token_url = "https://github.com/login/oauth/access_token";
        $this->user_info_url = "https://api.github.com/user";
    }

    function tokenData($code) {
        $result = [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'code'          => $code,
            'redirect_uri'  => $this->redirect_uri,
        ];
        return $result;
    }
    function redeemToken($token) {
        $data = self::curlExec($this->user_info_url, [
            "Authorization: token $token",
            "User-Agent: MyApp",
        ]);

        if (empty($data['login']) && empty($data['id'])) {
            $this->JsonResponse(['error' => 'User info fetch failed'], 400);
        }

        return [
            'provider_id' => $this->provider_id,
            'login' => $data['login'] ?: $data['id'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'response' => $data,
        ];
    }
    function loginParams() {
        $result = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            // 'scope' => 'read:user user:email',
            'allow_signup' => 'true',
        ];
        return $result;
    }
}
