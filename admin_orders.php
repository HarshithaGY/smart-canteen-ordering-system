<?php
require_once 'layout.php';
require_admin();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    if (in_array($status, ['Pending', 'Preparing', 'Ready', 'Delivered'], true)) {
        $s = $db->prepare('UPDATE orders SET status=? WHERE id=?');
        $s->bind_param('si', $status, $id);
        $s->execute();
        flash('Order status updated.');
    }
    redirect('admin_orders.php');
}

$orders = $db->query("SELECT o.*, u.full_name,
    GROUP_CONCAT(CONCAT(oi.item_name, ' x ', oi.quantity) ORDER BY oi.id SEPARATOR '||') AS ordered_items
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id, u.full_name
    ORDER BY o.id DESC");

page_header('Orders', true);
?>
<h1>Orders</h1>
<div class="cart-box">
    <table>
        <tr>
            <th>Token</th>
            <th>User</th>
            <th>Items Ordered</th>
            <th>Total</th>
            <th>Status</th>
            <th>Wait</th>
            <th>Update</th>
        </tr>
        <?php while ($o = $orders->fetch_assoc()): ?>
            <tr>
                <td>#<?=h($o['token_number'])?></td>
                <td><?=h($o['full_name'])?></td>
                <td>
                    <?php if (!empty($o['ordered_items'])): ?>
                        <ul class="order-items">
                            <?php foreach (explode('||', $o['ordered_items']) as $item): ?>
                                <li><?=h($item)?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="muted">No item details</span>
                    <?php endif; ?>
                </td>
                <td>Rs. <?=number_format($o['total_amount'], 0)?></td>
                <td><span class="badge <?=h(strtolower($o['status']))?>"><?=h($o['status'])?></span></td>
                <td><?=wait_minutes($o['status'])?> min</td>
                <td>
                    <form method="post" class="status-form">
                        <input type="hidden" name="id" value="<?=h($o['id'])?>">
                        <select name="status">
                            <option <?=$o['status'] === 'Pending' ? 'selected' : ''?>>Pending</option>
                            <option <?=$o['status'] === 'Preparing' ? 'selected' : ''?>>Preparing</option>
                            <option <?=$o['status'] === 'Ready' ? 'selected' : ''?>>Ready</option>
                            <option <?=$o['status'] === 'Delivered' ? 'selected' : ''?>>Delivered</option>
                        </select>
                        <button>Change</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php page_footer(); ?>
