<?php
ob_start();

$success_messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    include 'config.php';
/*
    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
    } else $errors[] = "Een product ID is verplicht.";

    if (isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === "add" || $action === "update") {
            $amount = $_POST['amount'] ?? 1;
        }
    } else $errors[] = "Een actie is verplicht.";
*/
    if (empty($errors)) {
        $stmt = $connection->prepare("SELECT Si.StockItemId, Si.StockItemName, ROUND(Si.TaxRate * Si.RecommendedRetailPrice / 100 + Si.RecommendedRetailPrice,2) as Price, (SELECT ImagePath FROM stockitemimages WHERE StockItemID = Si.StockItemID LIMIT 1) as ImagePath, (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = Si.StockItemID LIMIT 1) as BackupImagePath FROM stockitems Si WHERE StockItemID = ?;");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $result = ($result) ? $result->fetch_assoc() : false;
        $stmt->close();
        $connection->close();



    }
    include 'header.php';
} else {
    include 'header.php';
}


?>
<div class="container">
    <h1 class="mb-3">Afrekenen</h1>

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
                    <!--
                    <form method="POST" action="shopping-cart.php" class="mb-3">
                        <input type="hidden" name="product_id" id="product_id" value="<?= $product['StockItemId'] ?>">
                        <input type="hidden" name="action" value="update">
                        --!>

                        <div class="form-row">
                            <!--
                            <div class="col-sm-2">
                                <input min="1" required type="number" name="amount" class="form-control"
                                    placeholder="Aantal" value="<?= $product['amount'] ?>">
                            </div>
                            --!>
                            <!--
                            <div class="col-sm-2">
                                <button type="submit" class="btn btn-primary">Bijwerken</button>
                            </div>
                            --!>
                        </div>
                        <!--
                    </form>
                    <form method="POST" action="shopping-cart.php">
                        <input type="hidden" name="product_id" id="product_id" value="<?= $product['StockItemId'] ?>">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn btn-danger">Verwijderen</button>
                    </form>
                    --!>
                </div>
            </div>
            <hr class="border-white"/>
        <?php
    }
        /*
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
    $_SESSION["receipt_lines"]=$receipt_lines;
        */
    ?>

    <!-- Begin bottom div with totals -->
    <?php
    $receipt_lines = ($_SESSION["receipt_lines"]) ? $_SESSION["receipt_lines"] : array();
    ?>
    <div class="row bg-dark">
        <div class="col-12">
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
        </div>
    </div>
    <div>
        <div class="col-12">
        </div>
    </div>
    <div class="row bg-dark">
        <div class="col-12">
            <hr class="border-white">
            <div class="col-12">
                <form action="checkout.php" method="post">
                <div class="col-md-5 mb-3">
                    <div class="input-group">
                        <label for="adress"></label>
                        <select class="form-control" id="adress" required>
                            <option value=>kies een adres</option>
                            <option value="standaard_adres">standaard opgeven adres bij aanmaken account</option>
                            <option value="nieuw_adres">nieuw adres</option>
                        </select>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-secondary">gebruik
                            </button>
                        </div>
                    </div>
                    <label for="postcode">postcode</label>
                    <input type="text" class="form-control" id="postcode">
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <?php
    }
include "footer.php";
    ?>
