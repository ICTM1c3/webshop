<?php
include 'header.php';

function GetArrayWithCorrectKeys($array, $correctkey, $secondkey){
    for($i = 0; $i < count($array); $i++){
        $a[$array[$i][$correctkey]][$secondkey] = $array[$i][$secondkey];
    }
    return $a;
}

function PrintPrice($price){
    $cprice = round($price, 2);

    print(bcadd($cprice, 0, 2));
}

function PrintMaxCharsOfString($str, $chars){
    print(substr($str, 0, $chars));
}

function GetKeyWithStockItemID($array, $id){
    for($i = 0; $i < count($array); $i++){
        if($array[$i]["stockitemid"] == $id)
            return $i;
    }
}

$Query = "SELECT DISTINCT SI.StockItemID,
(RecommendedRetailPrice *(1 +(TaxRate / 100))) AS SellPrice, StockItemName, QuantityOnHand, SearchDetails,
(CASE WHEN (RecommendedRetailPrice *(1 +(TaxRate / 100))) > 50 THEN 0 ELSE 6.95 END) AS SendCosts,
MarketingComments, CustomFields, SI.Video,
(SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID)WHERE StockItemID = SI.StockItemID LIMIT 1) as BackupImagePath,
CONCAT('Opslagtemperatuur: ', (SELECT Temperature FROM coldroomtemperatures WHERE ColdRoomSensorNumber = CRT.ColdRoomSensorNumber
AND RecordedWhen = (SELECT MAX(RecordedWhen) FROM coldroomtemperatures WHERE ColdRoomSensorNumber = CRT.ColdRoomSensorNumber)), '&deg; C') AS Temperature
FROM stockitems SI
JOIN stockitemholdings SIH USING(stockitemid)
JOIN stockitemstockgroups ON SI.StockItemID = stockitemstockgroups.StockItemID
JOIN stockgroups USING(StockGroupID)
LEFT JOIN stockitemscoldroomtemperatures SICRT ON SI.StockItemID = SICRT.StockItemID
LEFT JOIN coldroomtemperatures CRT ON SICRT.ColdRoomSensorNumber = CRT.ColdRoomSensorNumber
WHERE SI.stockitemid = ?;";

$ShowStockLevel = 1000;
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_bind_param($Statement, "i", $_GET['id']);
mysqli_stmt_execute($Statement);
$ReturnableResult = mysqli_stmt_get_result($Statement);
if ($ReturnableResult && mysqli_num_rows($ReturnableResult) == 1) {
    $Result = mysqli_fetch_all($ReturnableResult, MYSQLI_ASSOC)[0];
} else $Result = null;
$geen_voorraad = "Dit product is momenteel onbeschikbaar";

//Get Images
$Query = "SELECT ImagePath FROM stockitemimages WHERE StockItemID = ?";

$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_bind_param($Statement, "i", $_GET['id']);
mysqli_stmt_execute($Statement);
$R = mysqli_stmt_get_result($Statement);
$R = mysqli_fetch_all($R, MYSQLI_ASSOC);

if ($R) {
    $Images = $R;
}
$Query = "SELECT stockitemid, ImagePath FROM stockitemimages sii WHERE stockitemid in 
          (select stockitemid from stockitemstockgroups where stockgroupid in (select stockgroupid from stockitemstockgroups where stockitemid = ?))";
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_bind_param($Statement, "i", $_GET['id']);
mysqli_stmt_execute($Statement);
$ItemImagePath = mysqli_stmt_get_result($Statement);
$ItemImagePath = mysqli_fetch_all($ItemImagePath, MYSQLI_ASSOC);//bevat stockitemid's en hun afbeeldingen van de categorie van het stockitem dat bekeken wordt
$ItemImagePath = GetArrayWithCorrectKeys($ItemImagePath, "stockitemid", "ImagePath");

$Query = "select stockgroupid, ImagePath from stockgroups where stockgroupid in (select stockgroupid from stockitemstockgroups where stockitemid = ?)";
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_bind_param($Statement, "i", $_GET['id']);
mysqli_stmt_execute($Statement);
$CatImagePath = mysqli_stmt_get_result($Statement);
$CatImagePath = mysqli_fetch_all($CatImagePath, MYSQLI_ASSOC);
//$CatImagePath = GetArrayWithCorrectKeys($CatImagePath, "stockgroupid", "ImagePath");

$Query = "select stockitemid, stockitemname, (RecommendedRetailPrice *(1 +(TaxRate / 100))) AS SellPrice from stockitems 
where stockitemid in (select stockitemid from stockitemstockgroups where stockgroupid in (select stockgroupid from stockitemstockgroups where stockitemid = ?))";
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_bind_param($Statement, "i", $_GET['id']);
mysqli_stmt_execute($Statement);
$RelatedStockItems = mysqli_stmt_get_result($Statement);
$RelatedStockItems = mysqli_fetch_all($RelatedStockItems, MYSQLI_ASSOC);
mysqli_close($connection);

?>

<div class="container mt-3">
    <?php
    if ($Result != null) {
        ?>
        <?php
        if (isset($Result['Video'])) { // If a video was loaded with the item, it's shown here
            ?>
            <div class="w-100 mb-3 video-container">
                <?= $Result['Video']; ?>
            </div>
            <?php
        }
        ?>

        <div class="row">
            <div class="col-sm-12 col-md-4">
                <?php
                if (isset($Images)) {
                    // print Single
                    if (count($Images) == 1) {
                        ?>
                        <div id="ImageFrame"
                             style="background-image: url('public/stockitemimg/<?php print $Images[0]['ImagePath']; ?>'); background-size: 300px; background-repeat: no-repeat; background-position: center;"></div>
                        <?php
                    } else if (count($Images) >= 2) { ?>
                        <div id="ImageFrame">
                            <div id="ImageCarousel" class="carousel slide" data-interval="false">
                                <!-- Indicators -->
                                <ul class="carousel-indicators">
                                    <?php for ($i = 0; $i < count($Images); $i++) {
                                        ?>
                                        <li data-target="#ImageCarousel"
                                            data-slide-to="<?php print $i ?>" <?php print(($i == 0) ? 'class="active"' : ''); ?>></li>
                                        <?php
                                    } ?>
                                </ul>

                                <!-- The slideshow -->
                                <div class="carousel-inner">
                                    <?php for ($i = 0; $i < count($Images); $i++) {
                                        ?>
                                        <div class="carousel-item <?php print ($i == 0) ? 'active' : ''; ?>">
                                            <img src="public/stockitemimg/<?php print $Images[$i]['ImagePath'] ?>">
                                        </div>
                                    <?php } ?>
                                </div>

                                <!-- Left and right controls -->
                                <a class="carousel-control-prev" href="#ImageCarousel" data-slide="prev">
                                    <span class="carousel-control-prev-icon carousel-control"></span>
                                </a>
                                <a class="carousel-control-next" href="#ImageCarousel" data-slide="next">
                                    <span class="carousel-control-next-icon carousel-control"></span>
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <img src="public/stockgroupimg/<?= $Result['BackupImagePath']; ?>" class="img-fluid" alt="">
                    <?php
                }
                ?>
            </div>
            <div class="col-sm-12 col-md-8">
                <h2 class="mb-0"><?= $Result['StockItemName']; ?></h2>
                <p class="mb-1 text-muted">Artikelnummer: <?= $Result["StockItemID"]; ?></p>
                <?php
                $voorraad = $Result['QuantityOnHand'];
                if ($voorraad == 0) {
                    $class = "text-danger";
                    $indicatie = "Binnenkort weer beschikbaar!";
                } else if ($voorraad <= 1000) {
                    $class = "text-warning";
                    $indicatie = "Lage voorraad, wees er snel bij!";
                } else if ($voorraad <= 25000) {
                    $class = "text-warning";
                    $indicatie = "Beperkte voorraad, koop snel!";
                } else if ($voorraad > 25000) {
                    $class = "text-success";
                    $indicatie = "Ruime Voorraad!";
                }
                ?>
                <p class="mb-1 font-weight-bold <?= $class ?? "" ?>"><?= $indicatie ?? "" ?></p>
                <?php
                if ($Result['QuantityOnHand'] > 0 ){ ?>
                    <p class='mb-1 text-success'> Voor 23:59 besteld morgen in huis <i class="fas fa-exclamation-circle"></i> </p>
                <?php
                }
                ?>
                <?php if (isset($Result['Temperature'])) { ?>
                    <p class="mb-1"><?= $Result['Temperature']; ?></p>
                <?php } ?>

                <div>
                    <p><span class="product-price">&euro;<?= number_format($Result['SellPrice'], 2, ',', '.'); ?></span>
                        <span class="text-muted">inclusief btw</span></p>
                </div>
                <?php
                if (isset($_GET['add'])) {
                    ?>
                    <div class="alert alert-success">Het product is toegevoegd aan de <u><a href="shopping-cart.php">winkelwagen</a></u>.
                    </div>
                    <?php
                }
                if (isset($_GET['addfail'])) {
                    ?>
                    <div class="alert alert-danger">Het product is niet toegevoegd aan de <u><a href="shopping-cart.php">winkelwagen</a></u>.
                    </div>
                    <?php
                }
                ?>
                <div>
                    <form action="shopping-cart.php?goto=<?= $_SERVER['REQUEST_URI'] ?>" method="POST">
                        <input type="hidden" name="product_id" value="<?= $Result['StockItemID'] ?>">
                        <input type="hidden" name="action" value="add">
                        <?php if ($Result["QuantityOnHand"] > 0) { ?>
                        <div class="form-row"> 
                            <!-- The div with the buttons active -->
                            <div class="col-sm-12 col-md-2">
                                <input min="1" required type="number" name="amount" class="form-control" placeholder="Aantal" value="1">
                            </div>
                            <div class="col mt-3 mt-md-0">
                                <button type="submit" class="btn btn-success">Toevoegen aan winkelwagen</button>
                            </div>
                        </div>
                        <?php } else { ?>
                        <div class="form-row"> 
                            <!-- The div with the buttons inactive -->
                            <div class="col-sm-12 col-md-2">
                                <input min="1" required type="number" name="amount" class="form-control" placeholder="Aantal" value="1" disabled>
                            </div>
                            <div class="col mt-3 mt-md-0">
                                <button type="submit" class="btn btn-danger disabled">Toevoegen aan winkelwagen</button>
                            </div>
                        </div>
                        <?php } ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="row my-3">
            <div class="col-sm-12 col-md-6 mb-3 mb-md-0">
                <div class="bg-secondary p-1">
                    <h3>Artikel beschrijving</h3>
                    <p><?php print $Result['SearchDetails']; ?></p>
                </div>
            </div>
            <div class="col-sm-12 col-md-6">
                <div class="bg-secondary p-1">
                    <h3>Specificaties</h3>
                    <?php
                    $CustomFields = json_decode($Result['CustomFields'], true);
                    if (is_array($CustomFields)) { ?>
                        <table>
                        <thead>
                        <th>Naam</th>
                        <th>Data</th>
                        </thead>
                        <?php
                        foreach ($CustomFields as $SpecName => $SpecText) { ?>
                            <tr>
                                <td>
                                    <?= $SpecName; ?>
                                </td>
                                <td>
                                    <?php
                                    if (is_array($SpecText)) {
                                        foreach ($SpecText as $SubText) {
                                            print $SubText . " ";
                                        }
                                    } else print $SpecText;
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </table><?php
                    } else { ?>
                        <p><?php print $Result['CustomFields']; ?>.</p>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    } else {
        ?><h2 id="ProductNotFound">Het opgevraagde product is niet gevonden.</h2><?php
    } ?>
    <div class="row">
        <hr class="border-white"></hr>
    </div>
    <div class="row">
        <h3>Gerelateerde producten: </h3>
    </div>
    <br>
    <div class="row" id="upxpos">
        <?php
        $aantalUpXSellProducts = 4;
        $UpXSellProducts = $RelatedStockItems;
        for($i = 0; $i < $aantalUpXSellProducts; $i++){
            $correctie = 0;
            if(GetKeyWithStockItemID($UpXSellProducts, $_GET['id']) + 1 + $i >= count($UpXSellProducts))
                $correctie = -count($UpXSellProducts);
            $products[$i] = GetKeyWithStockItemID($UpXSellProducts, $_GET['id']) + $correctie + 1 + $i;
        }
        for($a = 0; $a < $aantalUpXSellProducts; $a++){
            if(array_key_exists($UpXSellProducts[$products[$a]]["stockitemid"], $ItemImagePath)){
                $image = "stockitemimg/".$ItemImagePath[$UpXSellProducts[$products[$a]]["stockitemid"]]["ImagePath"];
            } else {
                for($i = 0; $i < count($CatImagePath); $i++) {
                    if($CatImagePath[$i]["ImagePath"] != null){
                        $image = "stockgroupimg/".$CatImagePath[$i]["ImagePath"];
                        break;
                    }
                }
            }
            ?>
            <div class="col-sm-12 col-md-3">
                <a href="view.php?id=<?php print($UpXSellProducts[$products[$a]]["stockitemid"]); ?>">
                    <div class="row" style="margin-top: 10px">
                        <div class="img-fluid" style="max-width: 400px; border: 10px solid rgba(255,255,255,.02);">
                            <img class="img-fluid" src="<?php print('public/'.$image);?>">
                        </div>
                    </div>
                    <div class="row">
                        <div style="min-height: 100px; max-width: 300px; border: 1px solid rgba(255,255,255,0);">
                            <p class="StockItemName" style="margin: 5px; min-height: 100px;"><?php PrintMaxCharsOfString($UpXSellProducts[$products[$a]]["stockitemname"],50); if(strlen($UpXSellProducts[$products[$a]]["stockitemname"]) > 50) print("...");?></p>
                        </div>
                    </div>
                </a>
                <div class="vr"></div>
                <form class="row" action="shopping-cart.php?goto=<?= $_SERVER['REQUEST_URI'] . '#upxpos' ?>" method="POST">
                    <input type="hidden" name="product_id" value="<?php print($UpXSellProducts[$products[$a]]["stockitemid"]); ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="page" value="<?php print($_GET['id']);?>">
                    <button type="submit" class="btn btn-success">In winkelwagen</button>
                    <span class="upxsell-price">€<?php PrintPrice($UpXSellProducts[$products[$a]]["SellPrice"]); ?></span>
                </form>
            </div>

        <?php
        }
        ?>
    </div>
</div>
<?php
include __DIR__ . "/footer.php";
?>
