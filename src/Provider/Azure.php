<?php
namespace Auth\Provider;
class Azure extends Provider {
    protected $prefix = 'AZURE';
    protected $provider_id = 1;
    protected $tenant = null;
    public $slug = 'azure';
    function __construct() {
        $this->client_id = $this->config('client_id');
        $this->client_secret = $this->config('client_secret');
        $this->tenant = $this->config('tenant');
        $this->redirect_uri = $this->config('redirect_uri');
        $this->scope = $this->config('scope', 'User.Read');

        $this->authorize_url = "https://login.microsoftonline.com/{$this->tenant}/oauth2/v2.0/authorize";
        $this->token_url = "https://login.microsoftonline.com/{$this->tenant}/oauth2/v2.0/token";
        $this->user_info_url = "https://graph.microsoft.com/v1.0/me";
    }
    function tokenData($code) {
        $result = [
            'client_id'     => $this->client_id,
            'scope'         => 'User.Read',
            'redirect_uri'  => $this->redirect_uri,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_secret' => $this->client_secret,
        ];
        return $result;
    }
    function redeemToken($token) {
        $data = $this->curlExec($this->user_info_url, [
            "Authorization: Bearer $token"
        ]);
        if (empty($data['userPrincipalName'])) {
            throw new \Exception("Invalid token data received from Azure");
        }
        return [
            'provider_id' => $this->provider_id,
            'login' => $data['userPrincipalName'],
            'email' => $data['userPrincipalName'],
            'name' => $data['displayName'] ?? null,
            'response' => $data,
        ];
    }
    function loginParams() {
        $result = [
            'client_id' => $this->client_id,
            'response_type' => 'code',
            'redirect_uri' => $this->redirect_uri,
            'response_mode' => 'query',
            'scope' => 'User.Read',
        ];
        return $result;
    }
}