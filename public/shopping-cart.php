<?php
ob_start();

$success_messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") { // Handle page actions
    include 'config.php';
    
    if (isset($_POST['action']) && !empty($_POST['action'])) { // Check wether 'action' field is empty
        $action = $_POST['action'];
        
        if ($action === "add" || $action === "update") {
            $amount = $_POST['amount'] ?? 1;
        }
    } else $errors[] = "Een actie is verplicht.";
    
    $promo_action = false;
    if ($action === "add_promocode" || $action === "remove_promocode") { // Set flag if promocode is to be used
        $promo_action = true;
    }

    if ($promo_action) {
        if (isset($_POST['promocode']) && !empty($_POST['promocode'])) { // Check wether 'promocode' field is empty
            $promocode = $_POST['promocode'];
        } else $errors[] = "Een kortingscode is verplicht.";
    }

    if (!$promo_action) {
        if (isset($_POST['product_id']) && !empty($_POST['product_id'])) { // Check wether 'product_id' field is empty
            $product_id = $_POST['product_id'];
        } else $errors[] = "Een product ID is verplicht.";
    }

    if (empty($errors)) {

        $result = false; // Default result to false, in case of errors
        if (!$promo_action) { // If not a promocode action, get stockitem information, and store in $result
            $stmt = $connection->prepare("SELECT Si.StockItemId, Si.StockItemName, ROUND(Si.TaxRate * Si.RecommendedRetailPrice / 100 + Si.RecommendedRetailPrice,2) as Price,  (SELECT ImagePath FROM stockitemimages WHERE StockItemID = Si.StockItemID LIMIT 1) as ImagePath,  (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = Si.StockItemID LIMIT 1) as BackupImagePath, sih.QuantityOnHand as voorraad FROM stockitems Si  JOIN stockitemholdings sih ON Si.stockitemid = sih.stockitemid WHERE Si.StockItemID = ?;");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $result = ($result) ? $result->fetch_assoc() : false;
            $stmt->close();
        }

        if ($promo_action) { // If a promocode action, get promocode information, and store in $result
            $stmt = $connection->prepare("SELECT type, value, minimum_price, maximum_price, itemSpecific FROM promocodes WHERE code = ? AND (valid_from < NOW() AND valid_until > NOW() OR valid_from IS NULL AND valid_until IS NULL);");
            $stmt->bind_param("s", $promocode);
            $stmt->execute();
            $result = $stmt->get_result();
            $result = ($result) ? $result->fetch_assoc() : false;
            $stmt->close();
        }

        if (isset($result)) {
            switch ($action) { // Handle the various page actions
                case "add":
                case "update":
                    // Toevoegen/bewerken van product aan/in winkelmand.

                    if ((int)$amount === 0) {
                        unset($_SESSION['shopping_cart'][$product_id]);
                        $success_messages[] = "Het product is verwijderd uit de winkelwagen.";
                        break;
                    }
                    $enoughStock = false;
                    if ($result["voorraad"] != 0 && $amount <= $result["voorraad"]){
                        $enoughStock = true;
                        if (isset($_SESSION['shopping_cart'][$product_id])&& $action == "add") {
                            $_SESSION['shopping_cart'][$product_id] = array_merge($result, ["amount" => $amount + $_SESSION['shopping_cart'][$product_id]['amount']]);
                        } else $_SESSION['shopping_cart'][$product_id] = array_merge($result, ["amount" => $amount]);
                    }



                    if ($action === "add") {
                        $connection->close();
                        if ($enoughStock) {
                            if(isset($_GET['goto'])) {
                                if(isset($_POST['upxproduct']))
                                    header("Location: $_GET[goto]");
                                else
                                    header("Location: $_GET[goto]&add");
                            } else {
                                header("Location: view.php?id=$product_id&add");
                            }
                        } else {
                            if(isset($_GET['goto'])) {
                                header("Location: $_GET[goto]&addfail");
                            } else {
                                header("Location: view.php?id=$product_id&addfail");
                            }
                        }

                        exit();
                    }

                    if ($enoughStock) {
                        $success_messages[] = "Het product is toegevoegd aan de winkelwagen.";
                    } else {
                        $errors[] = "Het product is niet toegevoegd aan de winkelwagen.";
                    }

                    break;

                case "remove":
                    // Verwijderen van product uit winkelmand.
                    
                    // TODO: make this handle deleting specific items
                    unset($_SESSION['shopping_cart'][$product_id]);
                    if (isset($_SESSION["promocode"]["specificPromocode"]) && isset($_SESSION["promocode"]["specificPromocodeItems"])) {
                        $allSpecificItemsCleared = true;
                        foreach ($_SESSION["promocode"]["specificPromocodeItems"] as $v) {
                            if (in_array($v, $_SESSION["shopping_cart"])) {
                                $allSpecificItemsCleared = false;
                                break;
                            }
                        }

                        if ($allSpecificItemsCleared) {
                            $_SESSION["promocode"]["specificPromocode"] = false;
                            $_SESSION["promocode"]["code"] = null;
                        }
                    }
                    $success_messages[] = "Het product is verwijderd uit de winkelwagen.";
                    break;

                case "add_promocode":
                    if ($result["itemSpecific"] == 1) { // If the promocode applies to one specific item
                        // Fetch the items for which the code is valid
                        $stmt = $connection->prepare("SELECT stockitem_id FROM promocodeStockitems WHERE promocode = ?;");
                        $stmt->bind_param("s", $promocode);
                        $stmt->execute();
                        $result2 = $stmt->get_result();
                        $result2 = ($result2) ? $result2->fetch_all(MYSQLI_ASSOC) : false;
                        $stmt->close();

                        $isValid = false; 
                        foreach ($result2 as $v) { // Check wether the promocode is valid for at least one item in the shopping cart
                            if (in_array($v["stockitem_id"], array_keys($_SESSION["shopping_cart"])) ) {
                                $isValid = true;
                            break;
                            }
                        }

                        if ($isValid) {
                            $_SESSION["promocode"]["code"] = $promocode;
                            $_SESSION["promocode"]["type"] = $result["type"];
                            $_SESSION["promocode"]["value"] = $result["value"];
                            if (isset($result["minimum_price"])) {
                                $_SESSION["promocode"]["minimum_price"] = $result["minimum_price"];
                            } else {
                                $_SESSION["promocode"]["minimum_price"] = null;
                            }
                            if (isset($result["maximum_price"])) {
                                $_SESSION["promocode"]["maximum_price"] = $result["maximum_price"];
                            } else {
                                $_SESSION["promocode"]["maximum_price"] = null;
                            }
                            $_SESSION["promocode"]["specificPromocode"] = true;
                            $_SESSION["promocode"]["specificPromocodeItems"] = array();
                            foreach ($result2 as $v) { // Add all items for which the promocode is valid to a session variable
                                array_push($_SESSION["promocode"]["specificPromocodeItems"], $v["stockitem_id"]);
                            }
                            $success_messages[] = "De kortingscode is toegepast.";
                        } else {
                            $errors[] = "Deze kortingscode is niet geldig voor de artikelen in uw winkelmand.";
                        }
                    } else { // If it is a 'full cart' promocode
                        $_SESSION["promocode"]["code"] = $promocode;
                        $_SESSION["promocode"]["type"] = $result["type"];
                        $_SESSION["promocode"]["value"] = $result["value"];
                        if (isset($result["minimum_price"])) {
                            $_SESSION["promocode"]["minimum_price"] = $result["minimum_price"];
                        } else {
                            $_SESSION["promocode"]["minimum_price"] = null;
                        }
                        if (isset($result["maximum_price"])) {
                            $_SESSION["promocode"]["maximum_price"] = $result["maximum_price"];
                        } else {
                            $_SESSION["promocode"]["maximum_price"] = null;
                        }
                        $success_messages[] = "De kortingscode is toegepast.";
                    }
                break;
                
                case "remove_promocode":
                    $_SESSION["promocode"]["code"] = null;
                    $_SESSION["promocode"]["specificPromocode"] = false;
                    $_SESSION["promocode"]["minimum_price"] = null;
                    $_SESSION["promocode"]["maximum_price"] = null;
                    $success_messages[] = "De kortingscode is verwijderd.";
                break;
                
                default:
                break;
            }
            $connection->close();
        } else {
            if (!$promo_action) {
                $errors[] = "Er is geen product gevonden met dit ID.";
            } else {
                $errors[] = "Dit is geen geldige kortingscode.";
            }
        }
    }
}
include 'header.php';

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
        $discount = 0;
        $receipt_lines = array();
        
        foreach ($products as $product_id_key => $product) {
            $productPrice = $product['Price'] * $product['amount']; // This code executes once for every item in the shopping cart
            $item_total += $productPrice;

            if (isset($_SESSION["promocode"]["code"]) && $_SESSION["promocode"]["type"] == "DYNAMIC") {
                if (isset($_SESSION["promocode"]["specificPromocode"]) && $_SESSION["promocode"]["specificPromocode"]) {
                    if (in_array($product_id_key, $_SESSION["promocode"]["specificPromocodeItems"])) {
                        $discount += -($productPrice * $_SESSION["promocode"]["value"]);
                    }
                } else {
                    $discount += -($productPrice * $_SESSION["promocode"]["value"]);
                }
            }
            
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

        // Calculate fixed promocode discount
        if (isset($_SESSION["promocode"]["code"]) && $_SESSION["promocode"]["type"] == "FIXED") {
            $discount = -$_SESSION["promocode"]["value"];
        }

        // Reset discount to 0 if the minimum or maximum price are not reached/gone over
        $maxMinBreached = false;
        if (isset($_SESSION["promocode"]["code"]) && ($_SESSION["promocode"]["minimum_price"] != null && $item_total <= $_SESSION["promocode"]["minimum_price"] || $_SESSION["promocode"]["maximum_price"] != null && $item_total >= $_SESSION["promocode"]["maximum_price"])) {
            $discount = 0;
            $maxMinBreached = true;
        }

        // Calculate subtotal
        $subtotal = max($item_total + $discount, 0);
        
        // Calculate shipping costs
        $shipping_costs = ($subtotal < 30) ? 5 : 0;
        
        // Calculate final total
        $total = max($subtotal + $shipping_costs, 0);
        

        // Create the receipt lines and push them to the array
        array_push($receipt_lines, array("NAME" => "Artikeltotaal", "VALUE" => "&euro;".number_format($item_total, 2, ',', '.')));
        if ($discount < 0) {
            array_push($receipt_lines, array("NAME" => "Korting (".$_SESSION["promocode"]["code"].")", "VALUE" => "&euro;".number_format($discount, 2, ',', '.')));
        }
        if ($maxMinBreached) {
            array_push($receipt_lines, array("NAME" => "Korting (".$_SESSION["promocode"]["code"].") (Deze kortingscode is niet geldig voor dit artikeltotaal)", "VALUE" => "&euro; 0,00"));
        }
        array_push($receipt_lines, array("NAME" => "Subtotaal incl. btw", "VALUE" => "&euro;".number_format($subtotal, 2, ',', '.')));
        array_push($receipt_lines, array("NAME" => "Verzendkosten", "VALUE" => ($shipping_costs == 0) ? "Gratis" : "&euro;".number_format($shipping_costs, 2, ',', '.')));
        array_push($receipt_lines, array("NAME" => "Totaal", "VALUE" => "&euro;".number_format($total, 2, ',', '.')));
        $_SESSION["receipt_lines"]=$receipt_lines;
        ?>

        <!-- Begin bottom div with promocode and totals -->
        <div class="row bg-dark">
            <div class="col-12">
                <div> <!-- Div with the promocode entry box and text -->
                    <form class="p-2" action="shopping-cart.php" method="POST">
                        <label for="kortingscodeveld">Kortingscode:</label>
                        <div class="input-group">
                            <input class="form-control" name="promocode" value="<?= (isset($_SESSION["promocode"]["code"])) ? $_SESSION["promocode"]["code"] : "" ?>" type="text">
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
                            <div class="col">
                                <strong><?=$line["NAME"]?></strong>
                            </div>
                           <div class="col text-right">
                               <span><?=$line["VALUE"]?></span>
                           </div>
                        </div>
                        <?php if ($key + 1 < count($receipt_lines)) { ?> <hr class="border-white"/> <?php } // Prints a horizontal line after the item if it's not the last in the list ?>
                        <?php
                    }
                    ?>
                </div>
                
                <hr class="border-white"/> 

                <a class="btn btn-primary btn-lg btn-block mb-4" href="login.php?goto=checkout.php" type="submit">Afrekenen</a> <!-- Knop afrekenen -->

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
<?php
include 'footer.php';
?>

