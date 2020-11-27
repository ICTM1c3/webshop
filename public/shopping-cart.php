<?php
ob_start();

$success_messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    include 'config.php';
    
    if (isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === "add" || $action === "update") {
            $amount = $_POST['amount'] ?? 1;
        }
    } else $errors[] = "Een actie is verplicht.";
    
    if ($action === "add_promocode" || $action === "remove_promocode") {
        $promo_action = true;
    } else {
        $promo_action = false;
    }

    if ($promo_action) {
        if (isset($_POST['promocode']) && !empty($_POST['promocode'])) {
            $promocode = $_POST['promocode'];
        } else $errors[] = "Een kortingscode is verplicht.";
    }

    if (!$promo_action) {
        if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
            $product_id = $_POST['product_id'];
        } else $errors[] = "Een product ID is verplicht.";
    }

    if (empty($errors)) {
        if (!$promo_action) {
            $stmt = $connection->prepare("SELECT Si.StockItemId, Si.StockItemName, ROUND(Si.TaxRate * Si.RecommendedRetailPrice / 100 + Si.RecommendedRetailPrice,2) as Price, (SELECT ImagePath FROM stockitemimages WHERE StockItemID = Si.StockItemID LIMIT 1) as ImagePath, (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = Si.StockItemID LIMIT 1) as BackupImagePath FROM stockitems Si WHERE StockItemID = ?;");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $result = ($result) ? $result->fetch_assoc() : false;
            $stmt->close();
            $connection->close();
        }

        if ($result || $promo_action) {
            switch ($action) {
                case "add":
                case "update":
                    // Toevoegen/bewerken van product aan/in winkelmand.

                    if ((int)$amount === 0) {
                        unset($_SESSION['shopping_cart'][$product_id]);
                        $success_messages[] = "Het product is verwijderd uit de winkelwagen.";
                        break;
                    }

                    if (isset($_SESSION['shopping_cart'][$product_id]) && $action == "add") {
                        $_SESSION['shopping_cart'][$product_id] = array_merge($result, ["amount" => $amount + $_SESSION['shopping_cart'][$product_id]['amount']]);
                    } else $_SESSION['shopping_cart'][$product_id] = array_merge($result, ["amount" => $amount]);

                    if ($action === "add") {
                        header("Location: view.php?id=$product_id&add");
                        exit();
                    }

                    $success_messages[] = "Het product is toegevoegd aan de winkelwagen.";
                    break;
                case "remove":
                    // Verwijderen van product uit winkelmand.
                    unset($_SESSION['shopping_cart'][$product_id]);
                    $success_messages[] = "Het product is verwijderd uit de winkelwagen.";
                    break;
                case "add_promocode":
                    $stmt = $connection->prepare("SELECT type, value, minimum_price, maximum_price FROM promocodes WHERE code = ? AND (valid_from < NOW() AND valid_until > NOW() OR valid_from IS NULL AND valid_until IS NULL);");
                    $stmt->bind_param("s", $promocode);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $result = ($result) ? $result->fetch_assoc() : false;
                    $stmt->close();
                    $connection->close();

                    if (!$result) {
                        $errors[] = "Dit is geen geldige kortingscode.";
                    } else {
                        $_SESSION["promocode"] = $promocode;
                        $success_messages[] = "Het kortingscode is toegepast.";
                    }
                    break;
                case "remove_promocode":
                    $_SESSION["promocode"] = null;
                    $success_messages[] = "De kortingscode is verwijderd.";
                    break;
                default:
                    break;
            }
        } else $errors[] = "Er is geen product gevonden met dit ID.";
    }
    include 'header.php';
} else {
    include 'header.php';
}

?>
<div class="container">
    <h1 class="mb-3">Winkelwagen</h1>
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


    <?php
    if (isset($_SESSION['shopping_cart']) && !empty($_SESSION['shopping_cart'])) {
        $products = $_SESSION['shopping_cart'];
        $item_total = 0;
        $receipt_lines = array();
        foreach ($products as $product) {
            $productPrice = $product['Price'] * $product['amount']; // This code executes once for every item in the shopping cart
            $item_total += $productPrice;
            
        ?>
            <div class="row"> <!-- This is one entry on the list of items in the shopping cart -->
                <div class="col-sm-6 col-md-3">
                    <img src="public/<?= isset($product['ImagePath']) ? "stockitemimg/" . $product['ImagePath'] : "stockgroupimg/" . $product['BackupImagePath'] ?>"
                        alt="" class="img-fluid">
                </div>
                <div class="col-sm-6 col-md-7">
                    <h3><a class="text-white" target="_blank"
                        href="view.php?id=<?= $product['StockItemId'] ?>"><?= $product['StockItemName'] ?></a></h3>
                    <p class="mb-1"><span>Artikelnummer: <?= $product['StockItemId'] ?></span></p>
                    <p>
                        <span>Prijs: &euro;<?= number_format($productPrice, 2, ',', '.') ?> (&euro;<?= number_format($product['Price'], 2, ',', '.') ?> per stuk)</span>
                    </p>
                    <form method="POST" action="shopping-cart.php" class="mb-3">
                        <input type="hidden" name="product_id" id="product_id" value="<?= $product['StockItemId'] ?>">
                        <input type="hidden" name="action" value="update">
                        <div class="form-row">
                            <div class="col-sm-2">
                                <input min="1" required type="number" name="amount" class="form-control"
                                    placeholder="Aantal" value="<?= $product['amount'] ?>">
                            </div>
                            <div class="col-sm-2">
                                <button type="submit" class="btn btn-primary">Bijwerken</button>
                            </div>
                        </div>
                    </form>
                    <form method="POST" action="shopping-cart.php">
                        <input type="hidden" name="product_id" id="product_id" value="<?= $product['StockItemId'] ?>">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn btn-danger">Verwijderen</button>
                    </form>
                </div>
            </div>
            <hr class="border-white"/> 
        <?php
    }
    // This code executes after the whole shopping cart list has been 'built'
    
    // Calculate shipping costs
    if ($item_total < 30) {
        $shipping_costs = 5;
    } else {
        $shipping_costs = 0;
    }
    
    // Calculate promocode
    $stmt = $connection->prepare("SELECT type, value, minimum_price, maximum_price FROM promocodes WHERE code = ? AND (valid_from < NOW() AND valid_until > NOW() OR valid_from IS NULL AND valid_until IS NULL);");
    $stmt->bind_param("s", $_SESSION["promocode"]);
    $stmt->execute();
    $promocode_discount = $stmt->get_result();
    $promocode_discount = ($promocode_discount) ? $promocode_discount->fetch_assoc() : false;
    $stmt->close();
    $connection->close();
    
    if ($promocode_discount) {
        if ($item_total > $promocode_discount["minimum_price"] || $promocode_discount["minimum_price"] == null && $item_total < $promocode_discount["maximum_price"] || $promocode_discount["maximum_price"] == null) {
            if ($promocode_discount["type"] === "FIXED") {
                $discount = -$promocode_discount["value"];
            } elseif ($promocode_discount["type"] === "DYNAMIC") {
                $discount = -($item_total * $promocode_discount["value"]);
            }
        }
    } else {
        $discount = 0;
    }

    // Calculate final total
    $total = max($item_total + $discount + $shipping_costs, 0);
    
    array_push($receipt_lines, array("NAME" => "Prijs artikelen", "VALUE" => "&euro;".number_format($item_total, 2, ',', '.')));
    if ($discount < 0) {
        array_push($receipt_lines, array("NAME" => "Kortingscode", "VALUE" => "&euro;".number_format($discount, 2, ',', '.')));
    }
    array_push($receipt_lines, array("NAME" => "Verzendkosten", "VALUE" => ($shipping_costs == 0) ? "Gratis" : "&euro;".number_format($shipping_costs, 2, ',', '.')));
    array_push($receipt_lines, array("NAME" => "Totaal", "VALUE" => "&euro;".number_format($total, 2, ',', '.')));
    ?>

    <!-- Begin bottom div with promocode and totals -->
    <div class="row bg-dark">
        <div class="col-12">
            <div> <!-- Div with the promocode entry box and text -->
                <form class="p-2" action="shopping-cart.php" method="POST">
                    <label for="kortingscodeveld">Kortingscode:</label>
                    <div class="input-group">
                        <input class="form-control" name="promocode" value="<?php if (isset($_SESSION["promocode"])) {print($_SESSION["promocode"]);} else {print("");} ?>" type="text">
                        <div class="input-group-append">
                            <button type="submit" name="action" value="add_promocode" class="btn btn-primary">Toepassen</button>
                            <button type="submit" name="action" value="remove_promocode" class="btn btn-danger">Verwijderen</button>
                        </div>
                    </div>
                </form>
            </div>

            <hr class="border-white"/> 
            
            <div class="col-12"> <!-- Div with the totals row -->
                <?php
                    foreach ($receipt_lines as $key => $line) {?>
                        <div class="row">
                            <span class="mr-5 ml-4"><strong><?=$line["NAME"]?></strong></span>
                            <span><?=$line["VALUE"]?></span>
                        </div>
                        <?php if ($key + 1 < count($receipt_lines)) { ?> <hr class="border-white"/> <?php } // Prints a horizontal line after the item if it's not the last in the list ?>
                        <?php
                    }
                    ?>
            </div>
            
            <hr class="border-white"/> 

            <a class="btn btn-primary btn-lg btn-block mb-4" href="checkout-login.php" type="submit">Afrekenen</a> <!-- Knop afrekenen -->

        </div>
    </div>
</div>
<?php
} else {
    ?>
    <p class="mb-1">Er zitten geen producten in de winkelwagen.</p>
    <p>Klik <a href="index.php">hier</a> om terug naar de homepagina te gaan.</p>
    <?php
}
?>
</div>
<?php
include 'footer.php';
?>

