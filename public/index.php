<?php
require '../vendor/autoload.php';
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

