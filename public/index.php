<?php
require '../vendor/autoload.php';
use Auth\Auth;
$auth = new Auth("Google");
// if ($auth->isLoggedIn()) {
//     $auth->JsonResponse(['status' => 'connected']);
// }
$auth->handleRequest();


// $provider = new Azure();
// // $provider = new Github();
// // $provider = new Google();
// if (isset($_GET['logout'])) {
//     $provider->logout();
// }
// if (isset($_GET['code'])) {
//     $provider->processCode($_GET['code']);
// }
// // if (isset($_GET['token'])) {
// //     $provider->processToken($_GET['token']);
// // }
// $provider->login();

