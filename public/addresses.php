<?php
ob_start();
include 'header.php';

$errors = [];
$success_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This code is executed if a post form is sent to the page
}
?>
<div class="container">
    <h1 class="mb-3">Uw geregistreerde adressen:</h1>
    
</div>
<?php
include 'footer.php';
?>
