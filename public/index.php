<?php
require '../vendor/autoload.php';
use Auth\Auth;
session_start();
$app_key = $_GET['app_key'] ?? null;
if (!$app_key) {
	die('App key is required');
}
$provider = ucfirst($_GET['provider'] ?? 'github');
$auth = new Auth($provider);
// $app = $auth->findAppByKey($app_key);
// if (!$app->is_active === 1) {
// 	die('App not found');
// }
// TODO - check if app is active
// TODO - check if provider is supported
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

