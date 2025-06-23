<?php
require '../vendor/autoload.php';
// use Azure as OAuth;
use Github as OAuth;
if (isset($_GET['logout'])) {
    OAuth::logout();
}
if (isset($_GET['code'])) {
    OAuth::gotCode($_GET['code']);
}
if (isset($_GET['token'])) {
    OAuth::gotToken($_GET['token']);
}
OAuth::login();

