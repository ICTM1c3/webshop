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
    <a class="text-white" href="logout.php"><u>Uitloggen</u></a>
</div>
<?php
include 'footer.php';
?>
