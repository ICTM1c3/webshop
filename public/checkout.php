<?php

$success_messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    include 'config.php';

    // de code die kijkt of er velden ontbreken
    if (isset($_POST["shipping_method"]) && !empty("shipping_method")) {
        $shipping_method = $_POST["shipping_method"];
    } else {
        $errors[] = "Je moet een verzendmethode kiezen.";
    }

    if (isset($_POST["delivery_date"]) && !empty("delivery_date")) {
        $delivery_date = $_POST["delivery_date"];
    } else {
        $errors[] = "Je moet een bezorgmoment kiezen.";
    }

    if (isset($_POST["payment_method"]) && !empty("payment_method")) {
        $payment_method = $_POST["payment_method"];
    } else {
        $errors[] = "Je moet een betaalmethode kiezen.";
    }

    if (isset($_POST["shipping_address"])) {
        $shipping_address = (int)$_POST["shipping_address"];
        if ($shipping_address === 0) {
            if (isset($_POST["street"]) && !empty($_POST["street"])) {
                $street = $_POST["street"];
            } else {
                $errors[] = "Je moet een straat en huisnummer invoeren.";
            }

            if (isset($_POST["postal_code"]) && !empty($_POST["postal_code"])) {
                $postal_code = $_POST["postal_code"];
            } else {
                $errors[] = "Je moet een postcode invoeren.";
            }

            if (isset($_POST["city"]) && !empty($_POST["city"])) {
                $city = $_POST["city"];
            } else {
                $errors[] = "Je moet een plaats invoeren.";
            }

            if (isset($_POST["country"]) && !empty($_POST["country"])) {
                $country = $_POST["country"];
            } else {
                $errors[] = "Je moet een land kiezen.";
            }

            if (empty($errors)) {
                $stmt = $connection->prepare("INSERT INTO address (user_id, street, city, postal_code, country) VALUES (?,?,?,?,?);");
                $stmt->bind_param("issss", $user["id"], $street, $city, $postal_code, $country);
                $result = $stmt->execute();
                $last_id = $connection->insert_id;
                $stmt->close();

                $shipping_address = $last_id;

                if (isset($_POST["billing_address"]) && !empty("billing_address")) {
                    $billing_address = (int)$_POST["billing_address"];
                    if ($billing_address == 0) {
                        $billing_address = $shipping_address;
                    }
                } else {
                    $errors[] = "Je moet een factuuradres selecteren.";
                }
            }
        }
    } else {
        $errors[] = "Je moet een verzendadres selecteren.";
    }

    if (isset($_SESSION["promocode"]["code"]) && $_SESSION["promocode"]["code"] != null) {
        $promocode = $_SESSION["promocode"]["code"];
    } else {
        $promocode = null;
    }
}

// de query die de order naar de database stuurt als er geen errors zijn
if (empty($errors) && isset($_POST["bestel_knop"])) {
    $stmt = $connection->prepare("INSERT INTO webshoporders (customer_id, deliverymethod_id, billing_address, shipping_address, payment_method, delivery_date, promocode) VALUES (?,?,?,?,?,?,?); ");
    $stmt->bind_param("iiiiiss", $user["id"],  $shipping_method, $billing_address, $shipping_address, $payment_method, $delivery_date, $promocode);
    $result = $stmt->execute();
    $last_id = $connection->insert_id;
    $stmt->close();

    foreach ($_SESSION["shopping_cart"] as $k => $v) {
        $stmt = $connection->prepare("INSERT INTO webshoporderstockitems (webshoporder_id, stockitem_id, amount) VALUES (?,?,?); ");
        $stmt->bind_param("iii", $last_id, $k, $v["amount"]);
        $result = $stmt->execute();
        $stmt->close();
    }
    $connection->close();

    ob_start();
    include_once(__DIR__ . '/../email-templates/order-confirmation.php');
    $html = ob_get_clean();

    if (isset($_SESSION['user']['email'])) {
        sendMail([$_SESSION['user']['email']], "Bestelbevestiging | NerdyGadgets", $html);

        if (isset($_ENV['ADMIN_EMAIL'])) {
            ob_start();
            include_once(__DIR__ . '/../email-templates/order-confirmation-admin.php');
            $html = ob_get_clean();

            sendMail([$_ENV['ADMIN_EMAIL']], "Nieuwe bestelling | NerdyGadgets", $html);
        }

        $_SESSION['shopping_cart'] = [];
        header("Location: orders.php?ordersuccess=1");
        exit();
    } else $errors[] = "Er is een fout opgetreden tijdens het versturen van de e-mail.";
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
                    <div class="col-sm-6 col-md-3 mb-3">
                        <label for="shipping_method">Verzendmethode</label>
                        <select class="custom-select d-block" id="shipping_method" name="shipping_method">
                            <option value="" disabled selected>Maak een keuze</option>
                            <?php

                            $stmt = $connection->prepare("select DeliveryMethodID, DeliveryMethodName from deliverymethods;");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $result = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : false;
                            $stmt->close();

                            foreach ($result as $v) {
                                ?>
                                <option value="<?= $v["DeliveryMethodID"] ?>"><?= $v["DeliveryMethodName"] ?></option>
                                <?php
                            }

                            ?>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-4 mb-3">
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
                        <label for="shipping_address">Verzendadres</label>
                        <select class="custom-select d-block" id="shipping_address" name="shipping_address">
                            <option value="" disabled selected>Maak een keuze</option>
                            <option value="0">Nieuw adres toevoegen</option>
                            <?php
                            $stmt = $connection->prepare("SELECT id, street, city, postal_code, country FROM address WHERE user_id = ? AND active = 1;");
                            $stmt->bind_param("i", $user["id"]);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $result = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : false;
                            $stmt->close();

                            foreach ($result as $v) {
                                ?>
                                <option value="<?= $v["id"] ?>"><?= $v["street"] . ", " . $v["city"] . " " . $v["postal_code"] . " " . $v["country"] ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label for="billing_address">Factuuradres</label>
                        <select class="custom-select d-block" id="billing_address" name="billing_address">
                            <option value="0" selected>Gebruik verzendadres</option>
                            <?php
                            $stmt = $connection->prepare("SELECT id, street, city, postal_code, country FROM address WHERE user_id = ? AND active = 1;");
                            $stmt->bind_param("i", $user["id"]);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $result = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : false;
                            $stmt->close();

                            foreach ($result as $v) {
                                ?>
                                <option value="<?= $v["id"] ?>"><?= $v["street"] . ", " . $v["city"] . " " . $v["postal_code"] . " " . $v["country"] ?></option>
                                <?php
                            }
                            ?>
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
                                <option value="" disabled>Maak een keuze</option>
                                <option value="NL" selected>Nederland</option>
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
                            <?php

                            $stmt = $connection->prepare("select id, payment_method from webshoppaymentmethods;");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $result = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : false;
                            $stmt->close();

                            foreach ($result as $v) {
                                ?>
                                <option value="<?= $v["id"] ?>"><?= $v["payment_method"] ?></option>
                                <?php
                            }

                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary mt-3" name="bestel_knop" type="submit">Bestelling plaatsen</button>
        </form>
    </div>
    <?php
    include "footer.php";
    ?>
