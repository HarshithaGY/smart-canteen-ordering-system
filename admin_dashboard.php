<?php
require_once 'layout.php';
require_admin();

$db = db();
$stats = [
    'orders' => $db->query('SELECT COUNT(*) c FROM orders')->fetch_assoc()['c'],
    'pending' => $db->query("SELECT COUNT(*) c FROM orders WHERE status='Pending'")->fetch_assoc()['c'],
    'revenue' => $db->query('SELECT COALESCE(SUM(total_amount),0) c FROM orders')->fetch_assoc()['c'],
    'users' => $db->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'],
];

$orders = $db->query("
    SELECT o.*, u.full_name,
        GROUP_CONCAT(CONCAT(oi.item_name, ' x ', oi.quantity) ORDER BY oi.id SEPARATOR '||') AS ordered_items
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id, u.full_name
    ORDER BY o.id DESC
    LIMIT 8
");

page_header('Admin Dashboard', true);
?>
<h1>Dashboard</h1>
<section class="stats">
    <div><span>Total Orders</span><b><?=h($stats['orders'])?></b></div>
    <div class="orange"><span>Pending Orders</span><b><?=h($stats['pending'])?></b></div>
    <div class="green"><span>Total Revenue</span><b>Rs. <?=number_format($stats['revenue'], 0)?></b></div>
    <div class="purple"><span>Total Users</span><b><?=h($stats['users'])?></b></div>
</section>

<div class="cart-box">
    <h2>Recent Orders</h2>
    <table>
        <tr>
            <th>Order ID</th>
            <th>User</th>
            <th>Items Ordered</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
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
                <td><?=date('d M Y', strtotime($o['created_at']))?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php page_footer(); ?>
