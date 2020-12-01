<?php
date_default_timezone_set('Europe/Amsterdam');

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env variables
$dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/../');
$dotenv->load();

// .env variables are required inside helpers.php
require_once __DIR__ . '/helpers.php';

$connection = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

if ($connection === false) exit("<h2>Website wordt op dit moment onderhouden.</h2>");

mysqli_set_charset($connection, 'latin1');

$status = session_status();

if($status == PHP_SESSION_NONE) {
    session_start();
}

$authenticated = false;

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    $authenticated = true;
    $user = $_SESSION['user'];
}