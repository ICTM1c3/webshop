<?php
ob_start();
include 'header.php';

if (!$authenticated) {
    header("Location: login.php?goto=orders.php");
    exit();
}

$query = "SELECT
    wo.id AS order_id,
    wosi.stockitem_id,
    wosi.amount,
    wo.promocode,
    si.RecommendedRetailPrice,
    si.TaxRate,
    wo.created_at,
    shipping_status,
    dm.DeliveryMethodName AS shipping_method,
    p.is_finalised,
    p.finalised_at,
    pc.type, pc.value, pc.itemSpecific,
    pcsi.stockitem_id AS promocode_stockitem_id
FROM webshoporderstockitems wosi
JOIN webshoporders wo ON wosi.webshoporder_id = wo.id
JOIN stockitems si ON si.StockItemID = wosi.stockitem_id
LEFT JOIN promocodes pc ON wo.promocode = pc.code
LEFT JOIN promocodeStockitems pcsi ON wosi.stockitem_id = pcsi.stockitem_id AND wo.promocode = pcsi.promocode
JOIN deliverymethods dm ON dm.DeliveryMethodID = wo.deliverymethod_id
LEFT JOIN payments p on wo.id = p.webshoporder_id AND p.paymentmethod_id = (SELECT MAX(paymentmethod_id) FROM payments WHERE p.webshoporder_id = wo.id AND p.is_finalised = 1)
WHERE customer_id = ?;";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$result = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : null;
$stmt->close();
$connection->close();

?>
<div class="container">
    <?php if(isset($_GET["ordersuccess"])){   ?>
        <div class="alert alert-success">Bestelling geplaatst!</div>
    <?php } ?>
    <h1>Mijn bestellingen</h1>
    <table class="table table-dark">
        <thead>
        <tr>
            <th scope="col">Bestelnummer</th>
            <th scope="col">Status</th>
            <th scope="col">Totaalbedrag</th>
            <th scope="col">Verzendmethode</th>
            <th scope="col">Besteldatum</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (count($result) > 0) {
            $order_ids = array_values(array_unique(array_map(function ($i) {
                return $i['order_id'];
            }, $result)));
            sort($order_ids);

            foreach ($order_ids as $key => $order_id) {
                $order = $result[array_search($order_id, array_column($result, 'order_id'))];
                if (!$order) continue;
                $total = 0;

                $order_items = array_filter(array_values(array_map(function ($i) use($order_id) {return ($i['order_id'] === $order_id) ? $i : null;}, $result)));

                foreach ($order_items as $order_item) {

                    $stockitem_price = (float) $order_item['RecommendedRetailPrice'];
                    $tax = (100 + (float) $order_item['TaxRate']) / 100;
                    $product_price = round($stockitem_price * $tax, 2);
                    $product_price = round($product_price * $order_item['amount'], 2);
                    $total += $product_price;

                    if (isset($order['promocode'])) {
                        if ($order['itemSpecific'] === 1 && $order_item['promocode_stockitem_id'] === $order_item['stockitem_id']) {
                            if ($order['type'] === "DYNAMIC") {
                                $total -= ($product_price * $order['value']);
                            } else $total -= $order['value'];
                        } elseif($order['itemSpecific'] === 0) {
                            if ($order['type'] === "DYNAMIC") {
                                $total -= ($product_price * $order['value']);
                            } else $total -= $order['value'];
                        }
                    }
                }
                ?>
                <tr>
                    <th scope="row">#<?= $order['order_id'] ?></th>
                    <td><?= ($order['shipping_status'] === 1) ? "Verzonden" : (($order['is_finalised'] === 1) ? "Betaald" : "Wachten op betaling") ?></td>
                    <td>&euro;<?= number_format($total, 2, ',', '.'); ?></td>
                    <td><?= $order['shipping_method'] ?></td>
                    <td><?= $order['created_at'] ?></td>
                    <td>
                        <a href="order.php?id=<?= $order['order_id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php
            }
        } else {
            ?>
            <h3>Je hebt nog geen bestellingen geplaatst.</h3>
            <h4>Ga naar de <u><a class="text-white" href="index.php">homepagina</a></u> om producten uit te zoeken.</h4>
            <?php
        }
        ?>
        </tbody>
    </table>
</div>
<?php
include 'footer.php';
?>
