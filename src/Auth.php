<?php
class Auth {
    protected $provider;

    public function __construct($provider) {
        $this->provider = $provider;
    }

    public function handleRequest() {
        if (isset($_GET['logout'])) {
            $this->provider->logout();
        } elseif (isset($_GET['code'])) {
            $this->provider->gotCode($_GET['code']);
        } elseif (isset($_GET['token'])) {
            $this->provider->gotToken($_GET['token']);
        } else {
            $this->provider->login();
        }
    }
    static function JsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}