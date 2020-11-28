<?php
include 'header.php';

function InArray($needle, $stack)
{
    for ($i = 0; $i < count($stack); $i++)
        if (in_array($needle, $stack[$i])) return true;
    return false;
}

$ReturnableResult = null;
$CategoryID = (isset($_GET['category_id'])) ? $_GET['category_id'] : ((isset($_GET['categoryfilter'])) ? $_GET['categoryfilter'] : "");
$SearchString = (isset($_GET['search_string'])) ? $_GET['search_string'] : "";
$ColorID = (isset($_GET['color_id'])) ? $_GET['color_id'] : "";
$Size = (isset($_GET['size'])) ? $_GET['size'] : "";
$Brand = (isset($_GET['brand'])) ? $_GET['brand'] : "";
$where = ($CategoryID == "") ? "" : "where s.stockitemid in (select stockitemid from stockitemstockgroups where stockgroupid = " . $CategoryID . ")";

$Query = "SELECT colorid, colorname FROM colors where colorid in (select s.colorid from stockitems s " . $where . ") ORDER BY colorid";
$Statement = mysqli_prepare($connection, $Query);
//mysqli_stmt_bind_param($Statement, "i", $CategoryID);
mysqli_stmt_execute($Statement);
$colors = mysqli_stmt_get_result($Statement);
$colors = mysqli_fetch_all($colors, MYSQLI_ASSOC);
if (count($colors) > 0) {
    if (!InArray($ColorID, $colors)) {
        $ColorID = "";
    }
} else {
    $ColorID = "";
}

$Query = "select distinct sa.size from stockitems sa 
            join stockitems s on sa.stockitemid = s.stockitemid
            " . $where;
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_execute($Statement);
$sizes = mysqli_stmt_get_result($Statement);
$sizes = mysqli_fetch_all($sizes, MYSQLI_ASSOC);
if (count($sizes) > 1) {
    if (!InArray($Size, $sizes)) {
        $Size = "";
    }
} else {
    $Size = "";
}

$Query = "select distinct sa.brand from stockitems sa 
            join stockitems s on sa.stockitemid = s.stockitemid
            " . $where;
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_execute($Statement);
$brands = mysqli_stmt_get_result($Statement);
$brands = mysqli_fetch_all($brands, MYSQLI_ASSOC);

if (count($brands) > 1) {
    if (!InArray($Brand, $brands)) {
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

$ProductsOnPage = ($ProductsOnPage > 0) ? $ProductsOnPage : 25;
$PageNumber = (isset($_GET['page_number'])) ? $_GET['page_number'] : 1;

$Sort = [
    'price_high_low' => 'SellPrice DESC',
    'price_low_high' => 'SellPrice',
    'name_high_low' => 'StockItemName DESC',
    'name_low_high' => 'StockItemName',
];

$Sort = $Sort[$SortOnPage] ?? 'StockItemName';

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
if ($CategoryID != "") {
    $subCat = " IN (SELECT StockGroupID from stockitemstockgroups WHERE StockItemID = SI.StockItemID)";
}

$colorsub = "-1 = -1";
if ($ColorID != "") {
    $colorsub = "IN (SELECT colorid from stockitems s WHERE s.StockItemID = SI.StockItemID)";
}

$sizesub = "-1 = -1";
if ($Size != "") {
    $sizesub = "IN (SELECT size from stockitems s WHERE s.StockItemID = SI.StockItemID)";
}

$brandsub = "-1 = -1";
if ($Brand != "") {
    $brandsub = "IN (SELECT brand from stockitems s WHERE s.StockItemID = SI.StockItemID)";
}

$Offset = $PageNumber * $ProductsOnPage;

$Query = "       SELECT SI.StockItemID, SI.StockItemName, SI.MarketingComments, ROUND(TaxRate * RecommendedRetailPrice / 100 + RecommendedRetailPrice,2) as SellPrice, QuantityOnHand, 
                (SELECT ImagePath
                FROM stockitemimages 
                WHERE StockItemID = SI.StockItemID LIMIT 1) as ImagePath,
                (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = SI.StockItemID LIMIT 1) as BackupImagePath
                FROM stockitems SI
                JOIN stockitemholdings SIH USING(stockitemid)
                WHERE (" . $queryBuildResult . ") AND
                 ? " . $subCat . " AND
                 ? " . $colorsub . " AND
                 ? " . $sizesub . " AND
                 ? " . $brandsub . "
                GROUP BY StockItemID
                ORDER BY " . $Sort . " 
                LIMIT ? OFFSET ?";
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_bind_param($Statement, "iissii", $CategoryID, $ColorID, $Size, $Brand, $ProductsOnPage, $Offset);
mysqli_stmt_execute($Statement);
$ReturnableResult = mysqli_stmt_get_result($Statement);
$ReturnableResult = mysqli_fetch_all($ReturnableResult, MYSQLI_ASSOC);

$amount = ($ReturnableResult != null) ? count($ReturnableResult[0]) : 0;
$AmountOfPages = (isset($amount)) ? ceil($amount / $ProductsOnPage) : 0;

$Query = "SELECT Stockgroupid, stockgroupname FROM stockgroups ORDER BY stockgroupid";
$Statement = mysqli_prepare($connection, $Query);
mysqli_stmt_execute($Statement);
$categories = mysqli_stmt_get_result($Statement);
$categories = mysqli_fetch_all($categories, MYSQLI_ASSOC);

?>

    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-4 mb-3 px-0">
                <div class="bg-dark border-white filter-container px-3 pt-3 pb-3 pb-md-0">
                    <a class="h2 mb-0 text-white" data-toggle="collapse" href="#filterCollapse" role="button"
                       aria-expanded="false" aria-controls="collapseExample">
                        <i class="fas fa-filter"></i> Filters <i class="fas fa-chevron-down d-md-none float-right"></i>
                    </a>
                    <form method="get" id="filterCollapse" class="collapse mt-3">
                        <div class="mb-3">
                            <h4><i class="fas fa-search"></i> Zoeken</h4>
                            <input type="text" name="search_string" id="search_string" class="form-submit"
                                   value="<?= (isset($_GET['search_string'])) ? htmlentities($_GET['search_string'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ""; ?>">
                        </div>

                        <div class="mb-3">
                            <h4><i class="fas fa-sort"></i> Categorie</h4>
                            <select name="categoryfilter" id="categoryfilter" onchange="this.form.submit()">>
                                <option value="">Alle</option>
                                <?php
                                for ($i = 0; $i < count($categories); $i++) {
                                    $selected = ($categories[$i]["Stockgroupid"] == $CategoryID) ? "selected" : "";
                                    print('<option value="' . $categories[$i]["Stockgroupid"] . '"' . $selected . '>' . $categories[$i]["stockgroupname"] . '</option>');
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <h4><i class="fas fa-sort"></i> Kleur</h4>
                            <select name="color_id" id="color_id" onchange="this.form.submit()">>
                                <option value="">Alle</option>

                                <?php
                                for ($i = 0; $i < count($colors); $i++) {
                                    $selected = ($colors[$i]["colorid"] == $ColorID) ? "selected" : "";

                                    print('<option value="' . $colors[$i]["colorid"] . '"' . $selected . '>' . $colors[$i]["colorname"] . '</option>');
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <h4><i class="fas fa-sort"></i> Grootte</h4>
                            <select name="size" id="size" onchange="this.form.submit()">>
                                <option value="">Alle</option>

                                <?php
                                for ($i = 0; $i < count($sizes); $i++) {
                                    if ($sizes[$i]["size"] == null) continue;
                                    $selected = ($sizes[$i]["size"] == $Size) ? "selected" : "";

                                    print('<option value="' . $sizes[$i]["size"] . '"' . $selected . '>' . $sizes[$i]["size"] . '</option>');
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <h4><i class="fas fa-sort"></i> Merk</h4>
                            <select name="brand" id="brand" onchange="this.form.submit()">>
                                <option value="">Alle</option>

                                <?php
                                for ($i = 0; $i < count($brands); $i++) {
                                    $selected = ($brands[$i]["brand"] == $Brand) ? "selected" : "";
                                    if ($brands[$i]["brand"] == null) continue;

                                    print('<option value="' . $brands[$i]["brand"] . '"' . $selected . '>' . $brands[$i]["brand"] . '</option>');
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <h4><i class="fas fa-list-ol"></i> Aantal producten per pagina</h4>
                            <input type="hidden" name="category_id" id="category_id"
                                   value="<?= (isset($_GET['category_id'])) ? $_GET['category_id'] : ""; ?>">

                            <select name="products_on_page" id="products_on_page" onchange="this.form.submit()">>
                                <option value="10" <?php if ($_SESSION['products_on_page'] == 10) {
                                    print "selected";
                                } ?>>10
                                </option>
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
                        </div>

                        <div class="mb-3">
                            <h4><i class="fas fa-sort"></i> Sorteren</h4>
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
                        </div>

                    </form>
                </div>
            </div>
            <div class="col-sm-12 col-md-8">
                <?php
                if (isset($ReturnableResult) && count($ReturnableResult) > 0) {
                    foreach ($ReturnableResult as $row) {
                        $image = (isset($row['ImagePath'])) ? "public/stockitemimg/" . $row['ImagePath'] : "public/stockgroupimg/" . $row['BackupImagePath'];
                        ?>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <a href="view.php?id=<?php print $row['StockItemID']; ?>">
                                    <img src="<?= $image ?>" class="img-fluid mb-2 mb-md-0" alt="">
                                </a>
                            </div>

                            <div class="col-sm-8">
                                <a href="view.php?id=<?php print $row['StockItemID']; ?>" class="text-white">
                                    <h3><?= $row["StockItemName"]; ?></h3>
                                </a>
                                <p class="mt-0 StockItemID">Artikelnummer: <?php print $row["StockItemID"]; ?></p>
                                <?= (isset($row["MarketingComments"]) && !empty($row['MarketingComments'])) ? "<p>$row[MarketingComments]</p>" : ""; ?>
                                <h6>&euro;<?= number_format($row["SellPrice"], 2, ',', '.'); ?> <span
                                            class="text-muted">incl. btw</span>
                                </h6>
                                <h6>
                                    <?php
                                    if ($row['QuantityOnHand'] < 1000) { ?>
                                        <p class='mb-1 text-danger'>Lage voorraad, wees er snel bij!</p>
                                    <?php } else if ($row['QuantityOnHand'] < 25000) { ?>
                                        <p class='mb-1 text-warning'> Beperkte voorraad, koop snel!</p>
                                    <?php } else if ($row['QuantityOnHand'] === 0) { ?>
                                        <p class='mb-1 text-danger'> Binnenkort weer beschikbaar!</p>
                                    <?php } else { ?>
                                        <p class='mb-1 text-success'> Ruime Voorraad!</p>
                                    <?php } ?>
                                </h6>
                            </div>
                        </div>
                        <hr class="border-white">
                        <?php
                    }
                    ?>
                    <form id="PageSelector">
                        <input type="hidden" name="search_string" id="search_string"
                               value="<?php if (isset($_GET['search_string'])) {
                                   print ($_GET['search_string']);
                               } ?>">
                        <input type="hidden" name="category_id" id="category_id"
                               value="<?php if (isset($_GET['category_id'])) {
                                   print ($_GET['category_id']);
                               } ?>">
                        <input type="hidden" name="result_page_numbers" id="result_page_numbers"
                               value="<?php print (isset($_GET['result_page_numbers'])) ? $_GET['result_page_numbers'] : "0"; ?>">
                        <input type="hidden" name="products_on_page" id="products_on_page"
                               value="<?php print ($_SESSION['products_on_page']); ?>">
                        <input type="hidden" name="sort" id="sort" value="<?php print ($_SESSION['sort']); ?>">


                        <?php
                        if ($AmountOfPages > 0) {
                            function generatePaginationUrl($page)
                            {
                                $params = $_GET;
                                $params['page_number'] = $page;
                                return http_build_query($params);
                            }

                            ?>
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item<?= ($PageNumber > 2) ? "" : " disabled" ?>"><a
                                                class="bg-dark border-dark page-link text-white<?= ($PageNumber > 2) ? "" : " text-muted" ?>"
                                                href="browse.php?<?= generatePaginationUrl($PageNumber - 1) ?>">Vorige
                                            pagina</a></li>
                                    <?php
                                    if ($PageNumber > 2) {
                                        ?>
                                        <li class="page-item"><a class="bg-dark border-dark page-link text-white"
                                                                 href="browse.php?<?= generatePaginationUrl($PageNumber - 2) ?>"><?= $PageNumber - 2; ?></a>
                                        </li>
                                        <?php
                                    }
                                    if ($PageNumber > 1) {
                                        ?>
                                        <li class="page-item"><a class="bg-dark border-dark page-link text-white"
                                                                 href="browse.php?<?= generatePaginationUrl($PageNumber - 1) ?>"><?= $PageNumber - 1; ?></a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <li class="page-item active"><a class="bg-dark border-dark page-link text-white"
                                                                    href="browse.php?<?= generatePaginationUrl($PageNumber) ?>"><?= $PageNumber; ?></a>
                                    </li>
                                    <?php
                                    if (($PageNumber + 1) <= $AmountOfPages) {
                                        ?>
                                        <li class="page-item"><a class="bg-dark border-dark page-link text-white"
                                                                 href="browse.php?<?= generatePaginationUrl($PageNumber + 1) ?>"><?= $PageNumber + 1; ?></a>
                                        </li>
                                        <?php
                                    }
                                    if (($PageNumber + 2) <= $AmountOfPages) {
                                        ?>
                                        <li class="page-item"><a class="bg-dark border-dark page-link text-white"
                                                                 href="browse.php?<?= generatePaginationUrl($PageNumber + 2) ?>"><?= $PageNumber + 2; ?></a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <li class="page-item<?= (($PageNumber + 1) <= $AmountOfPages) ? "" : " disabled"; ?>">
                                        <a class="bg-dark border-dark page-link text-white<?= (($PageNumber + 1) <= $AmountOfPages) ? "" : " text-muted"; ?>"
                                           href="browse.php?<?= generatePaginationUrl($PageNumber + 1) ?>">Volgende
                                            pagina</a></li>
                                </ul>
                            </nav>
                            <?php
                        }
                        ?>
                    </form>
                    <?php
                } else {
                    ?>
                    <h4>Er zijn geen producten gevonden.</h4>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
<?php
include 'footer.php';
?>