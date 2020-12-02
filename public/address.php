<?php
ob_start();
include 'header.php';

$errors = [];
$success_messages = [];
$address_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'config.php';
    // This code is executed if a post form is sent to the page

    if (isset($_POST['action']) && !empty($_POST['action'])) { // Check wether 'action' field is empty
        $action = $_POST['action'];
    } else $errors[] = "Een actie is verplicht.";

    if (isset($_POST['address_id']) && !empty($_POST['address_id'])) { // Check wether 'action' field is empty
        $address_id = $_POST['address_id'];
    } else $errors[] = "Een adres_id is verplicht.";

    if (isset($_POST["street"]) && !empty($_POST["street"])) {
        $street = $_POST["street"];
    } else {
        $errors[] = "Je moet een straat en huisnummer invoeren.";
    }

    if (isset($_POST["city"]) && !empty($_POST["city"])) {
        $city = $_POST["city"];
    } else {
        $errors[] = "Je moet een plaats invoeren.";
    }

    if (isset($_POST["postal_code"]) && !empty($_POST["postal_code"])) {
        $postal_code = $_POST["postal_code"];
    } else {
        $errors[] = "Je moet een postcode invoeren.";
    }

    if (isset($_POST["country"]) && !empty($_POST["country"])) {
        $country = $_POST["country"];
    } else {
        $errors[] = "Je moet een land kiezen.";
    }

    if (empty($errors)) {
        // This code executes only if no errors were encountered
        switch ($action) {
            case "update":
                $stmt = $connection->prepare("UPDATE address SET street = ?, city = ?, postal_code = ?, country = ? WHERE user_id = ? and id = ?;");
                $stmt->bind_param("ssssii", $street, $city, $postal_code, $country, $user["id"], $address_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $success_messages[] = "Het adres is aangepast.";
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
        <h1 class="mb-3">Adres bewerken</h1>
        <p><u><a class="text-white" href='addresses.php'>Terug naar het overzicht</a></u>.</p>
        <?php
        if (!(isset($address_id) && !empty($address_id)) && (isset($_GET["address_id"]) && !empty($_GET["address_id"]))) {
            $address_id = $_GET["address_id"];
        }
        if (isset($address_id) && !empty($address_id)) {
            ?>

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
                $stmt = $connection->prepare("SELECT id, street, city, postal_code, country FROM address WHERE user_id = ? AND id = ? AND active = 1;");
                $stmt->bind_param("ii", $user["id"], $address_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $result = ($result) ? $result->fetch_assoc() : false;

                if ($result) { ?>
                    <form method="POST" action="address.php">
                        <div class="form-row pb-2">
                            <div class="col-sm-12 col-md-4 mb-3">
                                <label for="street">Straat en huisnummer</label>
                                <input type="text" name="street" value="<?= $result["street"] ?>" id="street"
                                       class="form-control">
                            </div>
                            <div class="col-sm-12 col-md-4 mb-3">
                                <label for="city">Stad</label>
                                <input type="text" name="city" value="<?= $result["city"] ?>" id="city"
                                       class="form-control">
                            </div>
                            <div class="col-sm-12 col-md-4 mb-3">
                                <label for="postal_code">Postcode</label>
                                <input type="text" name="postal_code" value="<?= $result["postal_code"] ?>"
                                       id="postal_code" class="form-control">
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="country">Land</label>
                                <select class="custom-select d-block" value="<?= $result["country"] ?>" id="country"
                                        name="country">
                                    <option value="" disabled>Maak een keuze</option>
                                    <option value="NL" selected>Nederland</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row pb-2 pl-2">
                            <input type="hidden" name="address_id" value="<?= $address_id ?>">
                            <input type="hidden" name="action" value="update">
                            <button type="submit" class="btn btn-primary">Bevestigen</button>
                        </div>
                    </form>
                    <?php
                } else {
                    ?>
                    <div class="row">Er is geen adres gevonden met dit id.</div><?php
                }
                ?>
            </div>
            <?php
        } else { ?>
            Dit is geen geldig adres_id.
            <?php
        } ?>
        <?php
    } else { ?>
        <h1 class="mb-3">U moet ingelogd zijn om deze pagina te gebruiken.</h1>
        <?php
    } ?>
</div>
<?php
include 'footer.php';
?>
