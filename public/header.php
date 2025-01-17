<?php
include 'config.php';

$query = "SELECT StockGroupID, StockGroupName, ImagePath
          FROM stockgroups
          WHERE StockGroupID IN ( SELECT StockGroupID FROM stockitemstockgroups ) AND ImagePath IS NOT NULL
          ORDER BY StockGroupID ASC";
$Statement = mysqli_prepare($connection, $query);
mysqli_stmt_execute($Statement);
$HeaderStockGroups = mysqli_stmt_get_result($Statement);
?>
<!DOCTYPE html>
<html lang="en" style="background-color: rgb(35, 35, 47);">
<head>
    <meta charset="UTF-8">
    <title>NerdyGadgets</title>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- CSS -->
    <link rel="stylesheet" href="public/css/style.css" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
          integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx"
            crossorigin="anonymous"></script>


    <script src="public/app.js"></script>
    <script src="https://kit.fontawesome.com/cc11040cc7.js" crossorigin="anonymous"></script>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="57x57" href="public/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="public/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="public/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="public/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="public/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="public/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="public/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="public/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="public/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="public/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="public/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="public/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="public/favicon/favicon-16x16.png">
    <link rel="manifest" href="public/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="public/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
</head>
<body class="d-flex flex-column h-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="public/productimghighres/nerdygadgetslogo.png" loading="lazy">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="col-lg-6 col-sm-12 px-0 mt-3 mt-md-0">
            <form method="GET" action="browse.php" class="search">
                <div class="input-group w-100">
                    <input name="search_string" type="text" class="form-control" placeholder="Zoeken" value="<?= (isset($_GET['search_string'])) ? htmlentities($_GET['search_string'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ""; ?>">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <em class="fa fa-search"></em>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="shopping-cart.php">Winkelwagen<?= (isset($_SESSION['shopping_cart'])) ? " (" . count($_SESSION['shopping_cart']) . ")" : ""; ?></a>
            </li>
            <?php
            if($authenticated) {
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Welkom, <?= $user['name'] ?>!
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="orders.php">Mijn bestellingen</a>
                        <a class="dropdown-item" href="addresses.php">Mijn adressen</a>
                        <a class="dropdown-item" href="my-account.php">Mijn account</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="logout.php">Uitloggen</a>
                    </div>
                </li>
                <?php
            } else {
                ?>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Inloggen</a>
                </li>
                <?php
            }
            ?>
        </ul>
        </div>
    </div>
</nav>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <?php
                foreach($HeaderStockGroups as $HeaderStockGroup){
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="browse.php?category_id=<?= $HeaderStockGroup['StockGroupID'] ?>"><?= $HeaderStockGroup['StockGroupName'] ?></a>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
    </div>
</nav>
<main class="d-flex justify-content-start flex-fill">
    <div class="container-fluid mt-3">