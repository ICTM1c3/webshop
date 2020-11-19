<?php
include 'config.php';

if($authenticated) {
    unset($_SESSION['user']);
}

header("Location: index.php");
exit();
?>
