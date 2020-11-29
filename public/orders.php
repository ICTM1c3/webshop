<?php
ob_start();
include 'header.php';

if (!$authenticated) {
    header("Location: login.php?goto=orders.php");
    exit();
}

$query = "SELECT
	id,
    created_at,
	(SELECT COUNT(stockitem_id) FROM webshoporderstockitems WHERE webshoporder_id = webshoporders.id) AS product_count,
   	ROUND((SELECT
		SUM(amount * (si.RecommendedRetailPrice * (si.TaxRate / 100)))
		FROM webshoporderstockitems wosi
        JOIN stockitems si ON wosi.webshoporder_id = si.StockItemId
        WHERE webshoporder_id = webshoporders.id
	), 2) AS order_subtotal,
    dm.DeliveryMethodName AS shipping_method
FROM webshoporders
JOIN deliverymethods dm ON dm.DeliveryMethodID = webshoporders.deliverymethod_id
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
            foreach ($result as $order) {
            ?>
            <tr>
                <th scope="row">#<?= $order['id'] ?></th>
                <td><?= $order['id'] ?></td>
                <td>&euro;<?= number_format(($order['order_subtotal'] > 30) ? ($order['order_subtotal'] + 10) : $order['order_subtotal'], 2, ',', '.') ?></td>
                <td><?= $order['shipping_method'] ?></td>
                <td><?= $order['created_at'] ?></td>
                <td>
                    <a href="order.php?id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">
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
