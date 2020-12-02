<?php
ob_start();
include 'header.php';

$errors = [];
$success_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'config.php';
    // This code is executed if a post form is sent to the page

    if (isset($_POST['action']) && !empty($_POST['action'])) { // Check wether 'action' field is empty
        $action = $_POST['action'];
    } else $errors[] = "Een actie is verplicht.";

    if (isset($_POST['address_id']) && !empty($_POST['address_id'])) { // Check wether 'action' field is empty
        $address_id = $_POST['address_id'];
    } else $errors[] = "Een adres is verplicht.";

    if (empty($errors)) {
        // This code executes only if no errors were encountered
        switch ($action) {
            case "remove":
                $stmt = $connection->prepare("UPDATE address SET active = 0 WHERE user_id = ? and id = ?;");
                $stmt->bind_param("ii", $user["id"], $address_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $success_messages[] = "Het adres is verwijderd.";
                break;

            default:
                break;
        }
    }
}
?>
<div class="container">
    <?php
    if (isset($user)) { // Only show the addresses if the user is signed in?>
        <h1 class="mb-3">Mijn adressen</h1>
        <div>
        <?php
            foreach ($errors as $key => $value) {
                ?>
                <div class="alert alert-danger"><?= $value ?></div>
                <?php
            }

            foreach ($success_messages as $key => $value) {
                ?>
                <div class="alert alert-success"><?= $value ?></div>
                <?php
            }
        ?>
        </div>
        <div class="col-12 bg-dark p-3">
        <?php
            $stmt = $connection->prepare("SELECT id, street, city, postal_code, country FROM address WHERE user_id = ? AND active = 1;");
            $stmt->bind_param("i", $user["id"]);
            $stmt->execute();
            $result = $stmt->get_result();
            $result = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : false;

            if ($result) {
                foreach ($result as $k => $v) {
                    // This code executes once for ever address
                    ?><div class="row">
                        <div class="col"><?= $v["street"].", ".$v["city"]." ".$v["postal_code"]." ".$v["country"] ?></div>
                        <div class="col text-right col-md-auto">
                            <form method="GET" action="address.php">
                                <input type="hidden" name="address_id" value="<?= $v["id"] ?>">
                                <button type="submit" class="btn btn-primary">Bewerken</button>
                            </form>
                        </div>
                        <div class="col text-right col-md-auto">
                            <form method="POST" action="addresses.php">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="address_id" value="<?= $v["id"] ?>">
                                <button type="submit" class="btn btn-danger">Verwijderen</button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if ($k + 1 < count($result)) { ?> <hr class="border-white"/> <?php } // Prints a horizontal line after the item if it's not the last in the list ?>
                    <?php
                }
            } else {
                ?><p class="mb-1">Er zijn geen adressen bij ons bekend. Voeg er een toe door een bestelling te plaatsen.</p><?php
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
