<?php
session_start();
if(session_status() == PHP_SESSION_ACTIVE) {
    session_destroy();
    session_start();
}

header("Location: index.php");
exit();
?>
