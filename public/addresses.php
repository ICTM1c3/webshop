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
    <?= "id: ".$user["id"] ?>
    <?php
    if (isset($user)) { // Only show the addresses if the user is signed in?>
        <h1 class="mb-3">Uw geregistreerde adressen:</h1>
        <div class="col-12">
        <?php
            $stmt = $connection->prepare("SELECT street, city, postal_code, country FROM address WHERE user_id = ?;");
            $stmt->bind_param("i", $user["id"]);
            $stmt->execute();
            $result = $stmt->get_result();
            $result = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : false;

            if ($result) {
                foreach ($result as $v) {
                    // This code executes once for ever address
                    ?><div class="row"><?= $v["street"].", ".$v["city"]." ".$v["postal_code"]." ".$v["country"] ?></div><?php
                }
            } else {
                ?><div class="row">U hebt nog geen adressen geregistreerd.</div><?php
            }
        ?>
        </div>
        <?php 
    } else { ?>
        <h1 class="mb-3">U moet ingelogd zijn om deze pagina te gebruiken.</h1>
    <?php
    } ?>
</div>
<?php
include 'footer.php';
?>
