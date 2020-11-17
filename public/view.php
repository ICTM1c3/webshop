<?php
include 'header.php';

$Query = "SELECT DISTINCT SI.StockItemID,
(RecommendedRetailPrice *(1 +(TaxRate / 100))) AS SellPrice,
StockItemName,
CONCAT('Voorraad: ', QuantityOnHand) AS QuantityOnHand,
SearchDetails,
(
    CASE
        WHEN (RecommendedRetailPrice *(1 +(TaxRate / 100))) > 50 THEN 0
        ELSE 6.95
    END
) AS SendCosts,
MarketingComments,
CustomFields,
SI.Video,
(
    SELECT ImagePath
    FROM stockgroups
        JOIN stockitemstockgroups USING(StockGroupID)
    WHERE StockItemID = SI.StockItemID
    LIMIT 1
) as BackupImagePath,
CONCAT(
    'Opslagtemperatuur: ',
    (
        SELECT Temperature
        FROM coldroomtemperatures
        WHERE ColdRoomSensorNumber = CRT.ColdRoomSensorNumber
            AND RecordedWhen = (
                SELECT MAX(RecordedWhen)
                FROM coldroomtemperatures
                WHERE ColdRoomSensorNumber = CRT.ColdRoomSensorNumber
            )
),
'&deg; C'
) AS Temperature
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
} else {
    $Result = null;
}
//Get Images
$Query = "
            SELECT ImagePath
            FROM stockitemimages 
            WHERE StockItemID = ?";

$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_bind_param($Statement, "i", $_GET['id']);
mysqli_stmt_execute($Statement);
$R = mysqli_stmt_get_result($Statement);
$R = mysqli_fetch_all($R, MYSQLI_ASSOC);

if ($R) {
    $Images = $R;
}
mysqli_close($connection);
?>


<div id="CenteredContent">
    <?php
    if ($Result != null) {
    ?>
        <?php
        if (isset($Result['Video'])) { // If a video was loaded with the item, it's shown here
        ?>
            <div id="VideoFrame">
                <?php print $Result['Video']; // The contents of the database are an iframe style embed 
                ?>
            </div>
        <?php }
        ?>


        <div id="ArticleHeader">
            <?php
            if (isset($Images)) {
                // print Single
                if (count($Images) == 1) {
            ?>
                    <div id="ImageFrame" style="background-image: url('public/stockitemimg/<?php print $Images[0]['ImagePath']; ?>'); background-size: 300px; background-repeat: no-repeat; background-position: center;"></div>
                <?php
                } else if (count($Images) >= 2) { ?>
                    <div id="ImageFrame">
                        <div id="ImageCarousel" class="carousel slide" data-interval="false">
                            <!-- Indicators -->
                            <ul class="carousel-indicators">
                                <?php for ($i = 0; $i < count($Images); $i++) {
                                ?>
                                    <li data-target="#ImageCarousel" data-slide-to="<?php print $i ?>" <?php print(($i == 0) ? 'class="active"' : ''); ?>></li>
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
                <div id="ImageFrame" style="background-image: url('public/stockgroupimg/<?php print $Result['BackupImagePath']; ?>'); background-size: cover;"></div>
            <?php
            }
            ?>


            <h1 class="StockItemID">Artikelnummer: <?php print $Result["StockItemID"]; ?></h1>
            <h2 class="StockItemNameViewSize StockItemName">
                <?php print $Result['StockItemName']; ?>
            </h2>
            <ul class="ItemProperties">
                <li class="QuantityText"><?php print $Result['QuantityOnHand']; ?></li>
                <?php if (isset($Result['Temperature'])) {
                ?> <li class="TemperatureText"><?php print $Result['Temperature']; ?></li> <?php
            } ?>

            </ul>

            <div id="StockItemHeaderLeft">
                <div class="CenterPriceLeft">
                    <div class="CenterPriceLeftChild">
                        <p class="StockItemPriceText"><b><?php print sprintf("€ %.2f", $Result['SellPrice']); ?></b></p>
                        <h6> Inclusief BTW </h6>
                    </div>
                </div>
            </div>
        </div>

        <div id="StockItemDescription">
            <h3>Artikel beschrijving</h3>
            <p><?php print $Result['SearchDetails']; ?></p>
        </div>
        <div id="StockItemSpecifications">
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
                                <?php print $SpecName; ?>
                            </td>
                            <td>
                                <?php
                                if (is_array($SpecText)) {
                                    foreach ($SpecText as $SubText) {
                                        print $SubText . " ";
                                    }
                                } else {
                                    print $SpecText;
                                }
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
    <?php
    } else {
    ?><h2 id="ProductNotFound">Het opgevraagde product is niet gevonden.</h2><?php
                                                                            } ?>
</div>