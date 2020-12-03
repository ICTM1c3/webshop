<?php
include 'header.php';
//maak een query voor de producten die je op de homepagina wil hebben.
$Query = "SELECT SI.StockItemID, SI.StockItemName, SI.MarketingComments, stockgroups.StockGroupID, StockGroupName, 
            ROUND(SI.TaxRate * SI.RecommendedRetailPrice / 100 + SI.RecommendedRetailPrice,2) as SellPrice,
            (SELECT ImagePath FROM stockitemimages WHERE StockItemID = SI.StockItemID LIMIT 1) as ImagePath,
            (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = SI.StockItemID LIMIT 1) as BackupImagePath           
            FROM stockitems SI
            JOIN stockitemholdings SIH USING(stockitemid)
            JOIN stockitemstockgroups USING(StockItemID)
            JOIN stockgroups ON stockitemstockgroups.StockGroupID = stockgroups.StockGroupID
            WHERE SIH.QuantityOnHand > 0";

// stop de query in een statement en voer die uit.
// zodra de query is uitgevoerd sluit de connectie
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_execute($Statement);
$ReturnableResult = mysqli_stmt_get_result($Statement);
$ReturnableResult = mysqli_fetch_all($ReturnableResult, MYSQLI_ASSOC);
//de code van de hoofdpagina

$ITEMIMG = "public/stockitemimg/";
$GROUPIMG = "public/stockgroupimg/";

$categories = array_values(array_unique(array_map(function ($i) {return $i['StockGroupID'];}, $ReturnableResult)));

shuffle($categories);

$categories = array_slice($categories, 0, 5);

$Query = "
            SELECT StockGroupID, StockGroupName, ImagePath
            FROM stockgroups 
            WHERE StockGroupID IN (
                                    SELECT StockGroupID 
                                    FROM stockitemstockgroups
                                    ) AND ImagePath IS NOT NULL
            ORDER BY StockGroupID ASC";
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_execute($Statement);
$Result = mysqli_stmt_get_result($Statement);
$StockGroups = mysqli_fetch_all($Result, MYSQLI_ASSOC);
mysqli_close($connection);
?>
<div class="container">
    <div class="row mb-3">
        <div class="col-sm-12 mb-3">
            <h1 style="margin-left: 10px">Nerdy Gadgets</h1>
        </div>
    </div>
    <div class="row mb-3">
        <div id="Wrap">
            <?php
            if (isset($StockGroups)) {
                $i = 1;
                foreach ($StockGroups as $StockGroup) {
                    if ($i <= 6) {
                        ?>
                        <a href="<?php print "browse.php?category_id=";
                        print $StockGroup["StockGroupID"]; ?>">
                            <div id="StockGroup<?php print $i; ?>"
                                 style="background-image: url('public/stockgroupimg/<?php print $StockGroup["ImagePath"]; ?>')"
                                 class="StockGroups">
                                <h1 style="font-size: 1.4vw;"><?php print $StockGroup["StockGroupName"]; ?></h1>
                            </div>
                        </a>
                        <?php
                    }
                    $i++;
                }
            }
            ?>
        </div>
    </div>
    <div class="category-header">
        <?php
        foreach ($categories as $category) {
            ?>
            <div class="row mb-3">
                <?php
                $i = 0;
                $products = array_filter(array_values(array_map(function ($i) use($category) {return ($i['StockGroupID'] === $category) ? $i : null;}, $ReturnableResult)));
                shuffle($products);
                ?>
                <div class="col-sm-12 mb-3">
                    <h3>Aanbevolen <?= $products[0]['StockGroupName'] ?></h3>
                </div>
                <?php
                foreach ($products as $row) {
                    $i++;
                    ?>
                    <div class="col-sm-12 col-md-3">
                        <a href="view.php?id=<?= $row['StockItemID']; ?>">
                            <img src="<?= isset($row['ImagePath']) ? $ITEMIMG . $row['ImagePath'] : $GROUPIMG . $row['BackupImagePath'] ?>"
                                 class="img-fluid" alt="">
                        </a>
                        <h1 class="StockItemID">Artikelnummer: <?= $row["StockItemID"]; ?></h1>
                        <p class="StockItemName"><?= $row["StockItemName"]; ?></p>
                        <p class="StockItemName"><span
                                    class="HomePagePriceHighlight">&euro;<?= $row["SellPrice"] ?></span> inclusief btw
                        </p>
                    </div>
                    <?php
                    if ($i === 4) break;
                }
                ?>
                <div class="col-sm-12 text-right">
                    <a class="btn btn-secondary" href="browse.php?category_id=<?= $row['StockGroupID'] ?>" target="_blank">Bekijk alle <?= $products[0]['StockGroupName'] ?></a>
                </div>
            </div>
            <hr class="border-white">
            <?php
        }
        ?>
    </div>
</div>
<?php
include 'footer.php';
?>
