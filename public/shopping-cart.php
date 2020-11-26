<?php
ob_start();

$success_messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    include 'config.php';

    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
    } else $errors[] = "Een product ID is verplicht.";

    if (isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === "add" || $action === "update") {
            $amount = $_POST['amount'] ?? 1;
        }
    } else $errors[] = "Een actie is verplicht.";

    if (empty($errors)) {
        $stmt = $connection->prepare("SELECT Si.StockItemId, Si.StockItemName, ROUND(Si.TaxRate * Si.RecommendedRetailPrice / 100 + Si.RecommendedRetailPrice,2) as Price, (SELECT ImagePath FROM stockitemimages WHERE StockItemID = Si.StockItemID LIMIT 1) as ImagePath, (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = Si.StockItemID LIMIT 1) as BackupImagePath FROM stockitems Si WHERE StockItemID = ?;");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $result = ($result) ? $result->fetch_assoc() : false;
        $stmt->close();
        $connection->close();

        if ($result) {
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
    $totale_prijs = 0;
    foreach ($products as $product) {
        $totale_prijs += $product['Price'] * $product['amount'];
        ?>
        <?php
        //bereken de prijs inclusief de verzendkosten
        $korting = 10;
        $verzendkosten = 5;
        //er worden alleen verzendkosten gedeclareerd als het bedrag onder de 30 euro is.
        if($totale_prijs< 30){
            $totale_prijs_plus_verzendkosten = ($totale_prijs + $verzendkosten );
        } else{
            $verzendkosten = 0;
            $totale_prijs_plus_verzendkosten = ($totale_prijs + $verzendkosten );
        }
        $totale_prijs_plus_verzendkosten_metkorting =($totale_prijs_plus_verzendkosten- $korting);
        ?>
        <div class="row">
            <div class="col-sm-6 col-md-3">
                <img src="public/<?= isset($product['ImagePath']) ? "stockitemimg/" . $product['ImagePath'] : "stockgroupimg/" . $product['BackupImagePath'] ?>"
                     alt="" class="img-fluid">
            </div>
            <div class="col-sm-6 col-md-7">
                <h3><a class="text-white" target="_blank"
                       href="view.php?id=<?= $product['StockItemId'] ?>"><?= $product['StockItemName'] ?></a></h3>
                <p class="mb-1"><span>Artikelnummer: <?= $product['StockItemId'] ?></span></p>
                <p>
                    <span>Prijs: &euro;<?= number_format($product['Price'] * $product['amount'], 2, ',', '.') ?> (&euro;<?= number_format($product['Price'], 2, ',', '.') ?> per stuk)</span>
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
    ?>
    <div>
        <h5>Totale prijs: &euro;<?= number_format($totale_prijs, 2, ',', '.'); ?></h5>
    </div>
    <div class="row bg-dark">
        <div class="col-12">
            <form class="p-2" action="shopping-cart.php" method="post">
                <div class="form-row">
                    <div class="col-sm-12 col-md-4">
                    <label for="kortingscodeveld">Kortingscode:</label>
                    <div class="input-group">
                        <input class="form-control" name="kortingscodeveld" value="" type="text">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-secondary" name="kortingscodeknop" value="ok"> ok
                            </button>
                        </div>
                        <?php
                        if(isset($post_["kortingscodeknop"])){
                            $opgegevencode = $post_["kortingscodeveld"];
                            if($opgegevencode !== "gadgets"){
                                print"kortingscode niet herkend";
                        ?>
                    </div>
                        <h5>Verzendkosten: <?php print("€".$verzendkosten) ?></h5>
                        <h5>Totale prijs: <?php print("€".$totale_prijs_plus_verzendkosten) ?></h5>
                </div>
                </div>
                <?php
                }else{
                                ?>
                 </div>
                        <h5>Verzendkosten: <?php print("€".$verzendkosten) ?></h5>
                        <h5>korting:<?php print("€".$korting)?>     </h5>
                        <h5>Totale prijs: <?php print("€".$totale_prijs_plus_verzendkosten_metkorting) ?></h5>
                </div>
                </div>

             <?php   }
                            }
                ?>
                </div>
                        <h5>Verzendkosten: <?php print("€".$verzendkosten) ?></h5>
                        <h5>Totale prijs: <?php print("€".$totale_prijs_plus_verzendkosten) ?></h5>
                </div>
                </div>
            </form>
            <a class="btn btn-primary btn-lg btn-block" href="checkout-login.php" type="submit"> Afrekenen
            </a>
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

