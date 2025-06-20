<?php
require_once __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
return [
    'client_id' => $_ENV['OAUTH_CLIENT_ID'],
    'client_secret' => $_ENV['OAUTH_CLIENT_SECRET'],
    'redirect_uri' => $_ENV['OAUTH_REDIRECT_URI'],
    'tenant' => $_ENV['OAUTH_TENANT'],
    'authorize_url' => $_ENV['OAUTH_AUTHORIZE_URL'],
    'token_url' => $_ENV['OAUTH_TOKEN_URL'],
    'user_info_url' => $_ENV['OAUTH_USER_INFO_URL'],
];