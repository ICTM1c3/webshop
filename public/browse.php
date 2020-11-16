<?php
include 'header.php';

function InArray($needle, $stack){
    for($i = 0; $i < count($stack); $i++)
        if(in_array($needle, $stack[$i]))
            return true;
    return false;
}

$SearchString = "";
$ReturnableResult = null;
if (isset($_GET['search_string'])) {
    $SearchString = $_GET['search_string'];
}

$CategoryID = "";
if (isset($_GET['categoryfilter'])) {
    $CategoryID = $_GET['categoryfilter'];
}
else if (isset($_GET['category_id'])) {
    $CategoryID = $_GET['category_id'];
}

if (isset($_GET['color_id'])) {
    $ColorID = $_GET['color_id'];
}else{
    $ColorID = "";
}

if (isset($_GET['size'])) {
    $Size = $_GET['size'];
}else{
    $Size = "";
}

if (isset($_GET['brand'])) {
    $Brand = $_GET['brand'];
}else{
    $Brand = "";
}

$where = "where s.stockitemid in (select stockitemid from stockitemstockgroups where stockgroupid = ".$CategoryID.")";
if($CategoryID == "")
    $where = "";

$Query = "SELECT colorid, colorname FROM colors where colorid in (select s.colorid from stockitems s ".$where.") ORDER BY colorid";
$Statement = mysqli_prepare($connection, $Query);
//mysqli_stmt_bind_param($Statement, "i", $CategoryID);
mysqli_stmt_execute($Statement);
$colors = mysqli_stmt_get_result($Statement);
$colors = mysqli_fetch_all($colors, MYSQLI_ASSOC);
if(count($colors) > 0){
    if(!InArray($ColorID, $colors)){
        $ColorID = "";
    }
} else {
    $ColorID = "";
}

$Query = "select distinct sa.size from stockitems sa 
            join stockitems s on sa.stockitemid = s.stockitemid
            ".$where;
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_execute($Statement);
$sizes = mysqli_stmt_get_result($Statement);
$sizes = mysqli_fetch_all($sizes, MYSQLI_ASSOC);
if(count($sizes) > 1){
    if(!InArray($Size, $sizes)){
        $Size = "";
    }
} else {
    $Size = "";
}

$Query = "select distinct sa.brand from stockitems sa 
            join stockitems s on sa.stockitemid = s.stockitemid
            ".$where;
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_execute($Statement);
$brands = mysqli_stmt_get_result($Statement);
$brands = mysqli_fetch_all($brands, MYSQLI_ASSOC);

if(count($brands) > 1){
    if(!InArray($Brand, $brands)){
        $Brand = "";
    }
} else {
    $Brand = "";
}



if (isset($_GET['sort'])) {
    $SortOnPage = $_GET['sort'];
    $_SESSION["sort"] = $_GET['sort'];
} else if (isset($_SESSION["sort"])) {
    $SortOnPage = $_SESSION["sort"];
} else {
    $SortOnPage = "price_low_high";
    $_SESSION["sort"] = "price_low_high";
}

if (isset($_GET['products_on_page'])) {
    $ProductsOnPage = $_GET['products_on_page'];
    $_SESSION['products_on_page'] = $_GET['products_on_page'];
} else if (isset($_SESSION['products_on_page'])) {
    $ProductsOnPage = $_SESSION['products_on_page'];
} else {
    $ProductsOnPage = 25;
    $_SESSION['products_on_page'] = 25;
}
if (isset($_GET['page_number'])) {
    $PageNumber = $_GET['page_number'];
} else {
    $PageNumber = 0;
}

$AmountOfPages = 0;
$queryBuildResult = "";
switch ($SortOnPage) {
    case "price_high_low":
    {
        $Sort = "SellPrice DESC";
        break;
    }
    case "name_low_high":
    {
        $Sort = "StockItemName";
        break;
    }
    case "name_high_low";
        $Sort = "StockItemName DESC";
        break;
    case "price_low_high":
    {
        $Sort = "SellPrice";
        break;
    }
    default:
    {
        $Sort = "SellPrice";
        $SortName = "price_low_high";
    }
}
$searchValues = explode(" ", $SearchString);

$queryBuildResult = "";
if ($SearchString != "") {
    for ($i = 0; $i < count($searchValues); $i++) {
        if ($i != 0) {
            $queryBuildResult .= "AND ";
        }
        $queryBuildResult .= "SI.SearchDetails LIKE '%$searchValues[$i]%' ";
    }
    if ($queryBuildResult != "") {
        $queryBuildResult .= " OR ";
    }
    if ($SearchString != "" || $SearchString != null) {
        $queryBuildResult .= "SI.StockItemID ='$SearchString'";
    }
} else
    $queryBuildResult = "true";

$subCat = "-1 = -1";
if($CategoryID != ""){
    $subCat = " IN (SELECT StockGroupID from stockitemstockgroups WHERE StockItemID = SI.StockItemID)";
}

$colorsub =  "-1 = -1";
if($ColorID != ""){
    $colorsub = "IN (SELECT colorid from stockitems s WHERE s.StockItemID = SI.StockItemID)";
}

$sizesub = "-1 = -1";
if($Size != ""){
    $sizesub  = "IN (SELECT size from stockitems s WHERE s.StockItemID = SI.StockItemID)";
}

$brandsub = "-1 = -1";
if($Brand != ""){
    $brandsub = "IN (SELECT brand from stockitems s WHERE s.StockItemID = SI.StockItemID)";
}

$Offset = $PageNumber * $ProductsOnPage;

$ShowStockLevel = 1000;


$Query = "       SELECT SI.StockItemID, SI.StockItemName, SI.MarketingComments, ROUND(TaxRate * RecommendedRetailPrice / 100 + RecommendedRetailPrice,2) as SellPrice,
                (CASE WHEN (SIH.QuantityOnHand) >= ? THEN 'Ruime voorraad beschikbaar.' ELSE CONCAT('Voorraad: ',QuantityOnHand) END) AS QuantityOnHand, 
                (SELECT ImagePath
                FROM stockitemimages 
                WHERE StockItemID = SI.StockItemID LIMIT 1) as ImagePath,
                (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = SI.StockItemID LIMIT 1) as BackupImagePath
                FROM stockitems SI
                JOIN stockitemholdings SIH USING(stockitemid)
                WHERE (" . $queryBuildResult . ") AND
                 ? ".$subCat." AND
                 ? ".$colorsub." AND
                 ? ".$sizesub." AND
                 ? ".$brandsub."
                GROUP BY StockItemID
                ORDER BY " . $Sort . " 
                LIMIT ? OFFSET ?";
//var_dump($Query);
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_bind_param($Statement, "iiissii", $ShowStockLevel, $CategoryID, $ColorID, $Size, $Brand, $ProductsOnPage, $Offset);
mysqli_stmt_execute($Statement);
$ReturnableResult = mysqli_stmt_get_result($Statement);
$ReturnableResult = mysqli_fetch_all($ReturnableResult, MYSQLI_ASSOC);

$amount = 0;
if($ReturnableResult != null)
    $amount = count($ReturnableResult[0]);
if (isset($amount)) {
    $AmountOfPages = ceil($amount / $ProductsOnPage);
}

$Query = "SELECT Stockgroupid, stockgroupname FROM Stockgroups ORDER BY stockgroupid";
$Statement = mysqli_prepare($connection, $Query);
//mysqli_stmt_bind_param($Statement, "i", $CategoryID);
mysqli_stmt_execute($Statement);
$categories = mysqli_stmt_get_result($Statement);
$categories = mysqli_fetch_all($categories, MYSQLI_ASSOC);


?>

    <div id="FilterFrame"><h2 class="FilterText"><i class="fas fa-filter"></i> Filteren </h2>
        <form>
            <div id="FilterOptions">
                <h4 class="FilterTopMargin"><i class="fas fa-search"></i> Zoeken</h4>
                <input type="text" name="search_string" id="search_string"
                       value="<?php print (isset($_GET['search_string'])) ? htmlentities($_GET['search_string'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ""; ?>"
                       class="form-submit">

                <h4 class="FilterTopMargin"><i class="fas fa-sort"></i> Categorie</h4>

                <select name="categoryfilter" id="categoryfilter" onchange="this.form.submit()">>
                    <option value="">Alle
                    </option>

                    <?php
                    for($i = 0; $i < count($categories); $i++){
                        $selected = "";
                        if($categories[$i]["Stockgroupid"] == $CategoryID)
                            $selected = "selected";

                        print('
                    <option value="' .$categories[$i]["Stockgroupid"]. '"'.$selected.'>'.$categories[$i]["stockgroupname"].'
                    </option>
                    ');
                    }
                    ?>
                </select>

                <h4 class="FilterTopMargin"><i class="fas fa-sort"></i> Kleur</h4>

                <select name="color_id" id="color_id" onchange="this.form.submit()">>
                    <option value="">Alle
                    </option>

                    <?php
                    for($i = 0; $i < count($colors); $i++){
                        $selected = "";
                        if($colors[$i]["colorid"] == $ColorID)
                            $selected = "selected";

                        print('
                    <option value="' .$colors[$i]["colorid"]. '"'.$selected.'>'.$colors[$i]["colorname"].'
                    </option>
                    ');
                    }
                    ?>
                </select>

                <h4 class="FilterTopMargin"><i class="fas fa-sort"></i> Grootte</h4>

                <select name="size" id="size" onchange="this.form.submit()">>
                    <option value="">Alle
                    </option>

                    <?php
                    for($i = 0; $i < count($sizes); $i++){
                        $selected = "";
                        if($sizes[$i]["size"] == null)
                            continue;

                        if($sizes[$i]["size"] == $Size)
                            $selected = "selected";

                        print('
                    <option value="' .$sizes[$i]["size"]. '"'.$selected.'>'.$sizes[$i]["size"].'
                    </option>
                    ');
                    }
                    ?>
                </select>

                <h4 class="FilterTopMargin"><i class="fas fa-sort"></i> Merk</h4>

                <select name="brand" id="brand" onchange="this.form.submit()">>
                    <option value="">Alle
                    </option>

                    <?php
                    for($i = 0; $i < count($brands); $i++){
                        $selected = "";
                        if($brands[$i]["brand"] == null)
                            continue;

                        if($brands[$i]["brand"] == $Brand)
                            $selected = "selected";

                        print('
                    <option value="' .$brands[$i]["brand"]. '"'.$selected.'>'.$brands[$i]["brand"].'
                    </option>
                    ');
                    }
                    ?>
                </select>

                <h4 class="FilterTopMargin"><i class="fas fa-list-ol"></i> Aantal producten op pagina</h4>

                <input type="hidden" name="category_id" id="category_id"
                       value="<?php print (isset($_GET['category_id'])) ? $_GET['category_id'] : ""; ?>">

                <select name="products_on_page" id="products_on_page" onchange="this.form.submit()">>
                    <option value="25" <?php if ($_SESSION['products_on_page'] == 25) {
                        print "selected";
                    } ?>>25
                    </option>
                    <option value="50" <?php if ($_SESSION['products_on_page'] == 50) {
                        print "selected";
                    } ?>>50
                    </option>
                    <option value="75" <?php if ($_SESSION['products_on_page'] == 75) {
                        print "selected";
                    } ?>>75
                    </option>
                </select>
                <h4 class="FilterTopMargin"><i class="fas fa-sort"></i> Sorteren</h4>
                <select name="sort" id="sort" onchange="this.form.submit()">>
                    <option value="price_low_high" <?php if ($_SESSION['sort'] == "price_low_high") {
                        print "selected";
                    } ?>>Prijs oplopend
                    </option>
                    <option value="price_high_low" <?php if ($_SESSION['sort'] == "price_high_low") {
                        print "selected";
                    } ?> >Prijs aflopend
                    </option>
                    <option value="name_low_high" <?php if ($_SESSION['sort'] == "name_low_high") {
                        print "selected";
                    } ?>>Naam oplopend
                    </option>
                    <option value="name_high_low" <?php if ($_SESSION['sort'] == "name_high_low") {
                        print "selected";
                    } ?>>Naam aflopend
                    </option>
                </select>
        </form>
    </div>
    </div>
    <div id="ResultsArea" class="Browse">
        <?php
        if (isset($ReturnableResult) && count($ReturnableResult) > 0) {
            foreach ($ReturnableResult as $row) {
                ?>
                <a class="ListItem" href='view.php?id=<?php print $row['StockItemID']; ?>'>
                    <div id="ProductFrame">
                        <?php
                        if (isset($row['ImagePath'])) { ?>
                            <div class="ImgFrame"
                                 style="background-image: url('<?php print "Public/StockItemIMG/" . $row['ImagePath']; ?>'); background-size: 230px; background-repeat: no-repeat; background-position: center;"></div>
                        <?php } else if (isset($row['BackupImagePath'])) { ?>
                            <div class="ImgFrame"
                                 style="background-image: url('<?php print "Public/StockGroupIMG/" . $row['BackupImagePath'] ?>'); background-size: cover;"></div>
                        <?php }
                        ?>

                        <div id="StockItemFrameRight">
                            <div class="CenterPriceLeftChild">
                                <h1 class="StockItemPriceText"><?php print sprintf("€ %0.2f", $row["SellPrice"]); ?></h1>
                                <h6>Inclusief BTW </h6>
                            </div>
                        </div>
                        <h1 class="StockItemID">Artikelnummer: <?php print $row["StockItemID"]; ?></h1>
                        <p class="StockItemName"><?php print $row["StockItemName"]; ?></p>
                        <p class="StockItemComments"><?php print $row["MarketingComments"]; ?></p>
                        <h4 class="ItemQuantity"><?php print $row["QuantityOnHand"]; ?></h4>
                    </div>
                </a>
            <?php } ?>

            <form id="PageSelector">
                <input type="hidden" name="search_string" id="search_string"
                       value="<?php if (isset($_GET['search_string'])) {
                           print ($_GET['search_string']);
                       } ?>">
                <input type="hidden" name="category_id" id="category_id" value="<?php if (isset($_GET['category_id'])) {
                    print ($_GET['category_id']);
                } ?>">
                <input type="hidden" name="result_page_numbers" id="result_page_numbers"
                       value="<?php print (isset($_GET['result_page_numbers'])) ? $_GET['result_page_numbers'] : "0"; ?>">
                <input type="hidden" name="products_on_page" id="products_on_page"
                       value="<?php print ($_SESSION['products_on_page']); ?>">
                <input type="hidden" name="sort" id="sort" value="<?php print ($_SESSION['sort']); ?>">

                <?php
                if ($AmountOfPages > 0) {
                    for ($i = 1; $i <= $AmountOfPages; $i++) {
                        if ($PageNumber == ($i - 1)) {
                            ?>
                            <div id="SelectedPage"><?php print $i; ?></div><?php
                        } else { ?>
                            <button id="page_number" class="PageNumber" value="<?php print($i - 1); ?>" type="submit"
                                    name="page_number"><?php print($i); ?></button>
                        <?php }
                    }
                }
                ?>
            </form>
            <?php
        } else {
            ?>
            <h2 id="NoSearchResults">
                Yarr, er zijn geen resultaten gevonden.
            </h2>
            <?php
        }
        ?>
    </div>

<?php
include 'footer.php';
?>