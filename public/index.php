<?php
include 'header.php';
//maak een query voor de producten die je op de homepagina wil hebben.
$Query =  " 
 
              SELECT SI.StockItemID, SI.StockItemName, SI.MarketingComments, StockGroupName, 
                ROUND(SI.TaxRate * SI.RecommendedRetailPrice / 100 + SI.RecommendedRetailPrice,2) as SellPrice,
                (SELECT ImagePath FROM stockitemimages WHERE StockItemID = SI.StockItemID LIMIT 1) as ImagePath,
                (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = SI.StockItemID LIMIT 1) as BackupImagePath           
                FROM stockitems SI 
                JOIN stockitemholdings SIH USING(stockitemid)
                JOIN stockitemstockgroups USING(StockItemID)
                JOIN stockgroups ON stockitemstockgroups.StockGroupID = stockgroups.StockGroupID
                GROUP BY StockItemID";

// stop de query in een statement en voer die uit.
// zodra de query is uitgevoerd sluit de connectie
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_execute($Statement);
$ReturnableResult = mysqli_stmt_get_result($Statement);
$ReturnableResult = mysqli_fetch_all($ReturnableResult, MYSQLI_ASSOC);
mysqli_close($connection);
//de code van de hoofdpagina
?>
<div class="IndexStyle">
    <div class="col-11">
        <div><?php// de titel ?>
                <div class="homepaginaproducten">
                    <div class="homepaginatitel">
                        welkom op de website van nerdy gadgets
                        </div>
                    <?php // de structuur van de hoofdpagina is gedaan via tabellen, zodat je makkelijk de elementen een plek kan geven op de site ?>
                    <div style="margin-right: 200px">
                    <table class="homepaginatabel">
                        <th> <h3> De mode van dit seizoen </h3></th>
                        <tr></tr>
                    <?php
                    // de code kijkt naar elk resultaat van de query en voert daarvoor dan de code uit
                    $i =0;
                    foreach($ReturnableResult as $row) {
                        // als het product van de categorie is die ik op de pagina wil laten zien en als er minder dan 10 producten in rij staan, laat dan het product zien.
                        if($row['StockGroupName'] === "Clothing" && $i < 4){
                        ?>
                                <td>
                                    <?php // dit stuk zorgt er voor dat je op het plaatje kan klikken en dan naar de product pagina wordt gebracht ?>
                        <a class="ListItem" href='view.php?id=<?php print $row['StockItemID']; ?>'>
                           <?php // dit stuk zorgt er voor dat als er geen foto bij het product hoort er een standaard plaatje uit de categorie wordt gebruikt
                           if ($row['ImagePath'] != "") { ?>
                            <div class="ImgFrame"
                                 style="background-image: url('<?php print "Public/StockItemIMG/" . $row['ImagePath']; ?>'); background-size: 230px; background-repeat: no-repeat; background-position: center;"></div>
                            <?php } else if (isset($row['BackupImagePath'])) { ?>
                                <div class="ImgFrame"
                                     style="background-image: url('<?php print "Public/StockGroupIMG/" . $row['BackupImagePath'] ?>'); background-size: cover;"></div>
                            <?php }
                            ?>
                            <br>
                            <br>
                            <br>
                            <br>
                            <h1 class="StockItemID">Artikelnummer: <?php print $row["StockItemID"]; ?></h1>
                            <p class="StockItemName"><?php print $row["StockItemName"]; ?></p>
                            <p class="StockItemName"><?php print $row["SellPrice"] ?> inclusief BTW</p>
                                </td>
                    <?php $i++; }
                    }
                    ?>
                        <tr></tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td> <a href="browse.php?category_id=2"> bekijk de hele collectie</a> </td>
                        <tr></tr>
                        <th> Voor klein en groot</th>
                        <tr></tr>
                        <tr></tr>
                        <?php
                        // de code kijkt naar elk resultaat van de query en voert daarvoor dan de code uit
                        $i =0;
                        foreach($ReturnableResult as $row) {
                            // als het product van de categorie is die ik op de pagina wil laten zien en als er minder dan 10 producten in rij staan, laat dan het product zien.
                            if($row['StockGroupName'] === "Toys" && $i < 4){
                                ?>
                                <td>
                                    <?php // dit stuk zorgt er voor dat je op het plaatje kan klikken en dan naar de product pagina wordt gebracht ?>
                                    <a class="ListItem" href='view.php?id=<?php print $row['StockItemID']; ?>'>
                                        <?php // dit stuk zorgt er voor dat als er geen foto bij het product hoort er een standaard plaatje uit de categorie wordt gebruikt
                                        if ($row['ImagePath'] != "") { ?>
                                            <div class="ImgFrame"
                                                 style="background-image: url('<?php print "Public/StockItemIMG/" . $row['ImagePath']; ?>'); background-size: 230px; background-repeat: no-repeat; background-position: center;"></div>
                                        <?php } else if (isset($row['BackupImagePath'])) { ?>
                                            <div class="ImgFrame"
                                                 style="background-image: url('<?php print "Public/StockGroupIMG/" . $row['BackupImagePath'] ?>'); background-size: cover;"></div>
                                        <?php }
                                        ?>
                                        <br>
                                        <br>
                                        <br>
                                        <br>
                                        <h1 class="StockItemID">Artikelnummer: <?php print $row["StockItemID"]; ?></h1>
                                        <p class="StockItemName"><?php print $row["StockItemName"]; ?></p>
                                        <p class="StockItemName"><?php print $row["SellPrice"] ?> inclusief BTW</p>
                                </td>
                                <?php $i++; }
                        }
                        ?>
                        <tr></tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td> <a href="browse.php?category_id=9"> meer speelgoed</a> </td>
                        <tr></tr>
                        <tr></tr>
                        <th> gadgets voor nerden</th>
                        <tr></tr>
                        <?php
                        // de code kijkt naar elk resultaat van de query en voert daarvoor dan de code uit
                        $i =0;
                        foreach($ReturnableResult as $row) {
                            // als het product van de categorie is die ik op de pagina wil laten zien en als er minder dan 10 producten in rij staan, laat dan het product zien.
                            if($row['StockGroupName'] === "Computing Novelties" && $i < 4){
                                ?>
                                <td>
                                    <?php // dit stuk zorgt er voor dat je op het plaatje kan klikken en dan naar de product pagina wordt gebracht ?>
                                    <a class="ListItem" href='view.php?id=<?php print $row['StockItemID']; ?>'>
                                        <?php // dit stuk zorgt er voor dat als er geen foto bij het product hoort er een standaard plaatje uit de categorie wordt gebruikt
                                        if ($row['ImagePath'] != "") { ?>
                                            <div class="ImgFrame"
                                                 style="background-image: url('<?php print "Public/StockItemIMG/" . $row['ImagePath']; ?>'); background-size: 230px; background-repeat: no-repeat; background-position: center;"></div>
                                        <?php } else if (isset($row['BackupImagePath'])) { ?>
                                            <div class="ImgFrame"
                                                 style="background-image: url('<?php print "Public/StockGroupIMG/" . $row['BackupImagePath'] ?>'); background-size: cover;"></div>
                                        <?php }
                                        ?>
                                        <br>
                                        <br>
                                        <br>
                                        <br>
                                        <h1 class="StockItemID">Artikelnummer: <?php print $row["StockItemID"]; ?></h1>
                                        <p class="StockItemName"><?php print $row["StockItemName"]; ?></p>
                                        <p class="StockItemName"><?php print $row["SellPrice"] ?> inclusief BTW</p>
                                </td>
                                <?php $i++; }
                        }
                        ?>
                        <tr></tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td> <a href="browse.php?category_id=2"> bekijk de hele collectie</a> </td>
                        <tr></tr>
                        <tr></tr>
                        <th> De coolste novelty items</th>
                        <tr></tr>
                        <?php
                        // de code kijkt naar elk resultaat van de query en voert daarvoor dan de code uit
                        $i =0;
                        foreach($ReturnableResult as $row) {
                            // als het product van de categorie is die ik op de pagina wil laten zien en als er minder dan 10 producten in rij staan, laat dan het product zien.
                            if($row['StockGroupName'] === "Novelty Items" && $i < 4){
                                ?>
                                <td>
                                    <?php // dit stuk zorgt er voor dat je op het plaatje kan klikken en dan naar de product pagina wordt gebracht ?>
                                    <a class="ListItem" href='view.php?id=<?php print $row['StockItemID']; ?>'>
                                        <?php // dit stuk zorgt er voor dat als er geen foto bij het product hoort er een standaard plaatje uit de categorie wordt gebruikt
                                        if ($row['ImagePath'] != "") { ?>
                                            <div class="ImgFrame"
                                                 style="background-image: url('<?php print "Public/StockItemIMG/" . $row['ImagePath']; ?>'); background-size: 230px; background-repeat: no-repeat; background-position: center;"></div>
                                        <?php } else if (isset($row['BackupImagePath'])) { ?>
                                            <div class="ImgFrame"
                                                 style="background-image: url('<?php print "Public/StockGroupIMG/" . $row['BackupImagePath'] ?>'); background-size: cover;"></div>
                                        <?php }
                                        ?>
                                        <br>
                                        <br>
                                        <br>
                                        <br>
                                        <h1 class="StockItemID">Artikelnummer: <?php print $row["StockItemID"]; ?></h1>
                                        <p class="StockItemName"><?php print $row["StockItemName"]; ?></p>
                                        <p class="StockItemName"><?php print $row["SellPrice"] ?> inclusief BTW</p>

                                </td>
                                <?php $i++; }
                        }
                        ?>
                        <tr></tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td> <a href="browse.php?category_id=1"> bekijk de hele collectie</a> </td>
                        <tr></tr>
                    </table>
                    </div>
                </div>
        </div>
</div>
<?php
include 'footer.php';
?>

