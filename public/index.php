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
            JOIN stockgroups ON stockitemstockgroups.StockGroupID = stockgroups.StockGroupID;";

// stop de query in een statement en voer die uit.
// zodra de query is uitgevoerd sluit de connectie
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_execute($Statement);
$ReturnableResult = mysqli_stmt_get_result($Statement);
$ReturnableResult = mysqli_fetch_all($ReturnableResult, MYSQLI_ASSOC);
mysqli_close($connection);
//de code van de hoofdpagina

$ITEMIMG = "public/stockitemimg/";
$GROUPIMG = "public/stockgroupimg/";

$categories = array_values(array_unique(array_map(function ($i) {return $i['StockGroupID'];}, $ReturnableResult)));

shuffle($categories);

$categories = array_slice($categories, 0, 5);

?>
<div class="container">
    <div class="text-center my-5">
        <h2>Welkom op de website van Nerdy Gadgets!</h2>
    </div>
    <?php
    foreach ($categories as $category) {
        ?>
        <div class="row mb-5">
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
            <div class="text-right w-100 px-3">
                <a class="btn btn-secondary" href="browse.php?category_id=<?= $row['StockGroupID'] ?>" target="_blank">Bekijk alle <?= $products[0]['StockGroupName'] ?></a>
            </div>
        </div>
        <?php
    }
    ?>
</div>
<?php
include 'footer.php';
?>
