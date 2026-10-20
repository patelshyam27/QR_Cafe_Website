<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();

// KPI Calculations
$totalRevenue = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM cafe_orders WHERE order_status != 'CANCELLED'")->fetchColumn();
$totalOrders = $db->query("SELECT COUNT(*) FROM cafe_orders")->fetchColumn();
$activeBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'CONFIRMED'")->fetchColumn();
$menuItemsCount = $db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();

// Recent orders
$recentOrders = $db->query("
    SELECT * FROM cafe_orders 
    ORDER BY order_date DESC 
    LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard — QR Cafe</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f8fafc;">

  <!-- Admin Navbar -->
  <header class="navbar" style="background:#1e293b;border-bottom:none;">
    <div class="nav-container">
      <a class="brand" href="index.php" style="color:#f8fafc;">
        <span class="brand-icon">⚡</span>
        <span>QR Cafe Admin</span>
      </a>
      <ul class="nav-links">
        <li><a href="index.php" class="active" style="color:#fff;">Dashboard</a></li>
        <li><a href="kitchen.php" style="color:#cbd5e1;">Kitchen KDS</a></li>
        <li><a href="orders.php" style="color:#cbd5e1;">Orders</a></li>
        <li><a href="bookings.php" style="color:#cbd5e1;">Bookings</a></li>
        <li><a href="menu_manage.php" style="color:#cbd5e1;">Manage Menu</a></li>
        <li><a href="db_viewer.php" style="color:#60a5fa;font-weight:700;">DB Inspector</a></li>
        <li><a href="../index.php" target="_blank" style="color:#cbd5e1;">Customer Site ↗</a></li>
      </ul>
    </div>
  </header>

  <main class="main-wrapper">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h1 style="font-size: 2.2rem; font-weight: 800; color: #0f172a;">Executive Overview</h1>
        <p style="color: #64748b;">Live cafe performance, sales statistics, and orders tracking.</p>
      </div>
      <div style="display:flex;gap:10px;">
        <a href="kitchen.php" class="btn btn-primary">👨‍🍳 Open Kitchen Screen</a>
        <a href="db_viewer.php" class="btn btn-dark">🗄️ Database Inspector</a>
      </div>
    </div>

    <!-- KPI Grid -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-title">Total Revenue</div>
        <div class="kpi-val" style="color:#16a34a;">₹<?= number_format($totalRevenue, 2) ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-title">Total Orders Placed</div>
        <div class="kpi-val"><?= $totalOrders ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-title">Confirmed Bookings</div>
        <div class="kpi-val" style="color:#ea580c;"><?= $activeBookings ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-title">Active Menu Items</div>
        <div class="kpi-val"><?= $menuItemsCount ?></div>
      </div>
    </div>

    <!-- Recent Orders Table -->
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
      <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a;">Recent Incoming Orders</h2>
      <a href="orders.php" class="btn btn-sm btn-outline" style="color:#0f172a;border-color:#cbd5e1;">View All Orders ➔</a>
    </div>

    <div class="table-responsive">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Table</th>
            <th>Guest Name</th>
            <th>Date & Time</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="7" style="text-align:center;padding:2rem;color:#64748b;">No orders placed yet.</td></tr>
          <?php else: ?>
            <?php foreach ($recentOrders as $ro): ?>
              <tr>
                <td style="font-weight:700;">#<?= $ro['order_id'] ?></td>
                <td><span class="table-badge">Table <?= htmlspecialchars($ro['table_number']) ?></span></td>
                <td><?= htmlspecialchars($ro['customer_name']) ?></td>
                <td style="color:#64748b;font-size:0.85rem;"><?= date('M d, h:i A', strtotime($ro['order_date'])) ?></td>
                <td style="font-weight:800;color:#16a34a;">₹<?= number_format($ro['total_amount'], 2) ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($ro['order_status']) ?>">
                    <?= htmlspecialchars($ro['order_status']) ?>
                  </span>
                </td>
                <td>
                  <a href="../track_order.php?order_id=<?= $ro['order_id'] ?>" target="_blank" class="btn btn-sm btn-dark">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
