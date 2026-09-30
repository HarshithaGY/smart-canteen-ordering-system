<?php require_once 'config.php'; function page_header($title, $admin=false) { $user=current_user(); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($title)?> - Smart Canteen</title><link rel="stylesheet" href="assets/style.css"></head><body>
<?php if ($user): ?><div class="app-shell"><aside class="sidebar <?= $admin ? 'admin-side' : '' ?>"><div class="brand"><span class="brand-icon">🍴</span><strong><?= $admin ? 'Admin Panel' : 'Smart Canteen<br>Ordering System' ?></strong></div><nav>
<?php if ($admin): ?><a href="admin_dashboard.php">Dashboard</a><a href="admin_food_items.php">Food Items</a><a href="admin_orders.php">Orders</a><a href="admin_users.php">Users</a><a href="logout.php">Logout</a>
<?php else: ?><a href="menu.php">Home</a><a href="menu.php">Menu</a><a href="my_orders.php">My Orders</a><a href="cart.php">Cart <span><?=cart_count($user['id'])?></span></a><a href="profile.php">Profile</a><a href="logout.php">Logout</a><?php endif; ?>
</nav></aside><main class="main"><?php endif; ?>
<?php if ($m=flash()): ?><div class="flash"><?=h($m)?></div><?php endif; ?>
<?php } function page_footer() { if (current_user()) echo '</main></div>'; echo '</body></html>'; } ?>
