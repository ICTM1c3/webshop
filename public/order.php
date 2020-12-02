<?php
ob_start();
include 'header.php';

if (!$authenticated) {
    header("Location: login.php?goto=$_SERVER[REQUEST_URI]");
    exit();
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $order_id = $_GET['id'];
} else {
    header("Location: orders.php");
    exit();
}

$query = "SELECT
    si.StockItemID,
	si.StockItemName,
    si.TaxRate,
    si.RecommendedRetailPrice,
    wo.shipping_status,
    wo.promocode,
    wosi.amount,
    dm.DeliveryMethodName as shipping_method,
    p.is_finalised AS payment_is_finialised,
    p.finalised_at AS payment_finialised_at,
    wo.created_at,
    pc.type, 
    pc.value, 
    pc.minimum_price, 
    pc.maximum_price,
    pc.itemSpecific,
    pcsi.stockitem_id as promoItem
FROM webshoporders wo
JOIN deliverymethods dm ON dm.DeliveryMethodID = wo.deliverymethod_id
JOIN webshoporderstockitems wosi ON wosi.webshoporder_id = wo.id
JOIN stockitems si ON si.StockItemID = wosi.stockitem_id
LEFT JOIN payments p ON p.webshoporder_id = wo.id
LEFT JOIN promocodes pc ON wo.promocode = pc.code
LEFT JOIN promocodeStockitems pcsi ON wo.promocode = pcsi.promocode AND si.stockitemid = pcsi.stockitem_id
WHERE wo.customer_id = ? AND wo.id = ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("ii", $user['id'], $order_id);
$stmt->execute();
$result = $stmt->get_result();
$result = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : null;

//$query = "p.type, p.value, p.minimum_price, p.maximum_price from promocodes p where promocode = ?";

$stmt->close();
$connection->close();

if (is_null($result) || empty($result)) {
    header("Location: orders.php");
    exit();
}

$subtotal_price = 0;
$total_price = 0;

$shipping_method = ($result[0]) ? " (" . $result[0]['shipping_method'] . ")" : "";
$order_status = ($result[0]) ? (($result[0]['shipping_status'] === 1) ? "Verzonden" : (($result[0]['payment_is_finialised'] === 1) ? "Betaald" : "Wachten op betaling")) : "Onbekend";

?>
<div class="container">
    <h1>Bestelling #<?= $order_id ?></h1>
    <p class="mb-0">Status: <?= $order_status ?></p>
    <p><u><a class="text-white" href="orders.php">Terug naar overzicht.</a></u></p>
    <table class="table table-dark">
        <thead>
        <tr>
            <th scope="col">Naam</th>
            <th scope="col">Aantal</th>
            <th scope="col">Bedrag</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($result as $order) {
            $product_price = round($order['RecommendedRetailPrice'] * (1 + ($order['TaxRate'] / 100)), 2);
            $total_product_price = round($product_price * $order['amount'], 2);
            $total_price += $total_product_price;
            ?>
            <tr>
                <th scope="row">
                    <u><a target="_blank" class="text-white" href="view.php?id=<?= $order['StockItemID'] ?>"><?= $order['StockItemName'] ?></a></u>
                </th>
                <td><?= $order['amount'] ?></td>
                <td>
                    &euro;<?= number_format($total_product_price, 2, ',', '.') ?>
                    <?= ($order['amount'] > 1) ? "(&euro;" . number_format($product_price, 2, ',', '.') . " per stuk)" : "" ?>
                </td>
            </tr>
            <?php
        }
        $subtotal_price = $total_price;
        if($total_price < 30) $total_price += 30;

        $korting = 0;
        if($result[0]["promocode"] != null){
            if($result[0]["itemSpecific"] == 1){
                foreach ($result as $order) {
                    if($order["promoItem"] != null)
                        $korting += $order["value"] * $order['amount'] * $order['RecommendedRetailPrice'] * (1 + ($order['TaxRate'] / 100));

                }
            } else {
                $korting = $result[0]["value"] * $subtotal_price;
            }

            if($result[0]["minimum_price"] != null){
                if($result[0]["minimum_price"] > $subtotal_price){
                    $korting = 0;
                }
            }
            if($result[0]["maximum_price"] != null){
                if($result[0]["maximum_price"] < $subtotal_price){
                    $korting = 0;
                }
            }
            if($result[0]["type"] == "FIXED" && $korting != 0){
                $korting = $result[0]["value"];
            }
        }
        $total_price -= $korting;

        ?>
        <tr>
            <th style="font-size: 20px;">Subtotaal</th>
            <td></td>
            <td style="font-size: 20px;">&euro;<?= number_format($subtotal_price, 2, ',', '.') ?></td>
        </tr>
        <tr>
            <th><?php if($korting > 0) print("Kortingscode (".$result[0]["promocode"].")"); else print("Geen kortingscode");?></th>
            <td></td>
            <td><?php if($korting > 0) print("&euro;".number_format(-$korting, 2, ',', '.'));?></td>
        </tr>
        <tr>
            <th>Verzending<?= $shipping_method ?></th>
            <td></td>
            <td><?= ($subtotal_price < 30) ? "&euro;30,-" : "Gratis" ?></td>
        </tr>
        <tr>
            <th style="font-size: 24px;">Totaal</th>
            <td></td>
            <td style="font-size: 24px;">&euro;<?= number_format($total_price, 2, ',', '.') ?></td>
        </tr>
        </tbody>
    </table>
</div>
<?php
include 'footer.php';
?>
