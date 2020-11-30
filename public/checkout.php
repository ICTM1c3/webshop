<?php
ob_start();

$success_messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    include 'config.php';


    // de code die kijkt of er velden ontbreken
    if (isset($_POST["shipping_method"]) && !empty("shipping_method")) {
        $shipping_method = $_POST["shipping_method"];
    } else {
        $errors[] = "je moet een verzendmethode kiezen";
    }

    if (isset($_POST["delivery_date"]) && !empty("delivery_date")) {
        $delivery_date = $_POST["delivery_date"];
    } else {
        $errors[] = "je moet een bezorgmoment kiezen";
    }

    if (isset($_POST["payment_method"]) && !empty("payment_method")) {
        $payment_method = $_POST["payment_method"];
    } else {
        $errors[] = "je moet een betaalmethode kiezen";
    }

    if (isset($_POST["address"]) && !empty("address")) {
        if ($_POST["address"] == 0) {

            if (isset($_POST["street"]) && !empty("street")) {
                $street = $_POST["street"];
            } else {
                $errors[] = "je moet een straat kiezen";
            }

            if (isset($_POST["postal_code"]) && !empty("postal_code")) {
                $postal_code = $_POST["postal_code"];
            } else {
                $errors[] = "je moet een postcode invoeren";
            }

            if (isset($_POST["city"]) && !empty("city")) {
                $city = $_POST["city"];
            } else {
                $errors[] = "je moet een plaats opgeven";
            }

            if (isset($_POST["country"]) && !empty("county")) {
                $country = $_POST["country"];
            } else {
                $errors[] = "je moet een land kiezen";
            }

        } else {
            $address = $_POST["address"];
        }
    } else {
        $errors[] = "je moet een bezorgmoment kiezen";
    }

    if (empty($errors)) {
        $stmt = $connection->prepare("SELECT Si.StockItemId, Si.StockItemName, ROUND(Si.TaxRate * Si.RecommendedRetailPrice / 100 + Si.RecommendedRetailPrice,2) as Price, (SELECT ImagePath FROM stockitemimages WHERE StockItemID = Si.StockItemID LIMIT 1) as ImagePath, (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = Si.StockItemID LIMIT 1) as BackupImagePath FROM stockitems Si WHERE StockItemID = ?;");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $result = ($result) ? $result->fetch_assoc() : false;
        $stmt->close();
        $connection->close();
    }
}

include 'header.php';


?>


<div class="container checkout-container">
    <h1 class="mb-3">Afrekenen</h1>
    <p><u><a class="text-white" href="shopping-cart.php">Terug naar winkelmand</a></u></p>

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

        <div class="bg-dark">
            <?php
            if (isset($_SESSION['shopping_cart']) && !empty($_SESSION['shopping_cart'])) {
                $products = $_SESSION['shopping_cart'];
                $item_total = 0;
                $receipt_lines = ($_SESSION["receipt_lines"]) ? $_SESSION["receipt_lines"] : array();

                ?>

                <table class="table table-dark checkout-table">
                    <thead>
                    <tr>
                        <th scope="col"></th>
                        <th scope="col">Naam</th>
                        <th scope="col">Aantal</th>
                        <th scope="col">Prijs</th>
                        <th scope="col"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($products as $product) {
                        $productPrice = $product['Price'] * $product['amount']; // This code executes once for every item in the shopping cart
                        $item_total += $productPrice;
                        ?>
                        <tr>
                            <th scope="row" class="text-center"><img
                                        src="public/<?= isset($product['ImagePath']) ? "stockitemimg/" . $product['ImagePath'] : "stockgroupimg/" . $product['BackupImagePath'] ?>"
                                        alt="" class="img-fluid"></th>
                            <td><u><a class="text-white" target="_blank"
                                      href="view.php?id=<?= $product['StockItemId'] ?>"><?= $product['StockItemName'] ?></a></u>
                            </td>
                            <td><?= $product['amount'] ?></td>
                            <td>&euro;<?= number_format($productPrice, 2, ',', '.') ?>
                                (&euro;<?= number_format($product['Price'], 2, ',', '.') ?> per stuk)
                            </td>
                            <td>
                                <a href="#" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </td>
                        </tr>
                        <?php
                    }

                    foreach ($receipt_lines as $key => $line) { ?>
                        <tr>
                            <th scope="row"><?= $line["NAME"] ?></th>
                            <td></td>
                            <td></td>
                            <td class="font-weight-bold"><?= $line["VALUE"] ?></td>
                            <td></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
        </div>

        <form method="post" action="checkout.php">
            <div class="mt-3 bg-dark px-3">
                <h3 class="pt-2">Verzendgegevens</h3>
                <div class="form-row pt-2">
                    <div class="col-sm-6 mb-3">
                        <label for="shipping_method">Verzendmethode</label>
                        <select class="custom-select d-block" id="shipping_method" name="shipping_method">
                            <option value="" disabled selected>Maak een keuze</option>
                            <option value="3">Delivery Van</option>
                            <option value="1">Post</option>
                            <option value="2">Courier</option>
                        </select>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label for="delivery_date">Bezorgmoment</label>
                        <select class="custom-select d-block" id="delivery_date" name="delivery_date">
                            <option value="" disabled selected>Maak een keuze</option>
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                $date = date('Y-m-d', strtotime(date('Y-m-d') . " +$i weekday"));
                                echo "<option value='$date'>$date</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-row pt-2">
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label for="address">Adres</label>
                        <select class="custom-select d-block" id="address" name="address">
                            <option value="" disabled selected>Maak een keuze</option>
                            <option value="0">Nieuw adres toevoegen</option>
                            <option value="1">Voorbeeld 1a, 1111AA, NL</option>
                            <option value="2">Voorbeeld 1a, 1111AA, NL</option>
                            <option value="3">Voorbeeld 1a, 1111AA, NL</option>
                            <option value="4">Voorbeeld 1a, 1111AA, NL</option>
                        </select>
                    </div>
                </div>
                <div class="new-address-form" style="display: none;">
                    <div class="form-row pb-2">
                        <div class="col-sm-12 col-md-4 mb-3">
                            <label for="street">Straat en huisnummer</label>
                            <input type="text" name="street" id="street" class="form-control">
                        </div>
                        <div class="col-sm-12 col-md-4 mb-3">
                            <label for="city">Stad</label>
                            <input type="text" name="city" id="city" class="form-control">
                        </div>
                        <div class="col-sm-12 col-md-4 mb-3">
                            <label for="postal_code">Postcode</label>
                            <input type="text" name="postal_code" id="postal_code" class="form-control">
                        </div>
                        <div class="col-sm-12 col-md-6 mb-3">
                            <label for="country">Land</label>
                            <select class="custom-select d-block" id="country" name="country">
                                <option value="" disabled selected>Maak een keuze</option>
                                <option value="NL">Nederland</option>
                                <option value="DE">Duitsland</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 bg-dark px-3">
                <h3 class="pt-2">Betaalgegevens</h3>
                <div class="form-row py-2">
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label for="payment_method">Betaalmethode</label>
                        <select class="custom-select d-block" id="payment_method" name="payment_method">
                            <option value="" disabled selected>Maak een keuze</option>
                            <option value="ideal">iDEAL</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary mt-3" name="bestel_knop" type="submit">Bestelling plaatsen</button>
        </form>
    </div>
    <?php
    // de query die de order naar de database stuurt als er geen errors zijn
    if (count($errors) === 0 && isset($_POST["bestel_knop"])) {
        mysqli_report(MYSQLI_REPORT_ALL);

        $stmt = $connection->prepare("INSERT INTO users (first_name, last_name, email, password, created_at) VALUES (?, ?, ?, ?, ?);");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $date);
        $date = date('Y-m-d H:i:s');

        $result = $stmt->execute();
        $stmt->close();
        $connection->close();

        if (!$result) {
            $errors[] = "Er is een fout opgetreden.";
        } else $success_messages[] = "Je bestelling is geplaatst.";
    }

    include "footer.php";
    ?>
