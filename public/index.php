<?php
require '../vendor/autoload.php';
var_dump(__LINE__, $_COOKIE);die; // Debugging line
if (isset($_COOKIE['token'])) {
    Auth::JsonResponse(['status' => 'connected']);
}
$provider = new Azure();
// $provider = new Github();
// $provider = new Google();
if (isset($_GET['logout'])) {
    $provider->logout();
}
if (isset($_GET['code'])) {
    $provider->gotCode($_GET['code']);
}
// if (isset($_GET['token'])) {
//     $provider->gotToken($_GET['token']);
// }
$provider->login();

