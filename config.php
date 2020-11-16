<?php
require_once './vendor/autoload.php';

// Load .env variables
$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->load();

$connection = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

if ($connection === false) exit("<h2>Website wordt op dit moment onderhouden.</h2>");

mysqli_set_charset($connection, 'latin1');

session_start();