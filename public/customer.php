<?php
ob_start();
include 'header.php';

if (!$authenticated) {
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
