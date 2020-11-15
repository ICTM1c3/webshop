<?php
ob_start();
include 'header.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_id']) && empty($_SESSION['user_id']))) {
    header("Location: login.php");
    exit();
}
?>
<div class="container">
    Je bent ingelogd!
    <a href="logout.php">Uitloggen</a>
</div>
<?php
include 'footer.php';
?>
