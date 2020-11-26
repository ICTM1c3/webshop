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
        $no_product_id = true;
    }

    if (isset($_POST['product_id']) && !empty($_POST['product_id'] || $no_product_id)) {
        $product_id = $_POST['product_id'];
    } else $errors[] = "Een product ID is verplicht.";

    if (empty($errors)) {
        if (!$no_product_id) {
            $stmt = $connection->prepare("SELECT Si.StockItemId, Si.StockItemName, ROUND(Si.TaxRate * Si.RecommendedRetailPrice / 100 + Si.RecommendedRetailPrice,2) as Price, (SELECT ImagePath FROM stockitemimages WHERE StockItemID = Si.StockItemID LIMIT 1) as ImagePath, (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = Si.StockItemID LIMIT 1) as BackupImagePath FROM stockitems Si WHERE StockItemID = ?;");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $result = ($result) ? $result->fetch_assoc() : false;
            $stmt->close();
            $connection->close();
        }

        if ($result || $no_product_id) {
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
                    $_SESSION['promocode'] = "test";
                    print_r($_SESSION);
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

    if ($item_total < 30) { // Calculate shipping costs
        $shipping_costs = 5;
    } else {
        $shipping_costs = 0;
    }

    // Calculate promocode
    $discount = -10;

    // Calculate final total
    $total = max($item_total + $discount + $shipping_costs, 0);
    
    array_push($receipt_lines, array("NAME" => "Prijs artikelen", "VALUE" => $item_total));
    if ($discount < 0) {
        array_push($receipt_lines, array("NAME" => "Kortingscode", "VALUE" => $discount));
    }
    array_push($receipt_lines, array("NAME" => "Verzendkosten", "VALUE" => $shipping_costs));
    array_push($receipt_lines, array("NAME" => "Totaal", "VALUE" => $total));
    ?>

    <!-- Begin bottom div with promocode and totals -->
    <div class="row bg-dark">
        <div class="col-12">
            <div> <!-- Div with the promocode entry box and text -->
                <form class="p-2" action="shopping-cart.php" method="POST">
                    <label for="kortingscodeveld">Kortingscode:</label>
                    <div class="input-group">
                        <input class="form-control" name="promocode" value="" type="text">
                        <input type="hidden" name="action" value="add_promocode">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-secondary">Toepassen</button>
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
                            <span>&euro;<?=number_format($line["VALUE"], 2, ',', '.')?></span>
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

