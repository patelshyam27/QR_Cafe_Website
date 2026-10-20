<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();

$statusFilter = $_GET['status'] ?? 'all';
$query = "SELECT * FROM cafe_orders";
$params = [];
if ($statusFilter !== 'all') {
    $query .= " WHERE order_status = ?";
    $params[] = strtoupper($statusFilter);
}
$query .= " ORDER BY order_date DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Orders — QR Cafe Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f8fafc;">

  <header class="navbar" style="background:#1e293b;border-bottom:none;">
    <div class="nav-container">
      <a class="brand" href="index.php" style="color:#f8fafc;">
        <span class="brand-icon">⚡</span>
        <span>QR Cafe Admin</span>
      </a>
      <ul class="nav-links">
        <li><a href="index.php" style="color:#cbd5e1;">Dashboard</a></li>
        <li><a href="kitchen.php" style="color:#cbd5e1;">Kitchen KDS</a></li>
        <li><a href="orders.php" class="active" style="color:#fff;">Orders</a></li>
        <li><a href="bookings.php" style="color:#cbd5e1;">Bookings</a></li>
        <li><a href="menu_manage.php" style="color:#cbd5e1;">Manage Menu</a></li>
        <li><a href="db_viewer.php" style="color:#60a5fa;">DB Inspector</a></li>
      </ul>
    </div>
  </header>

  <main class="main-wrapper">
    <div style="margin-bottom:2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
      <div>
        <h1 style="font-size:2.2rem;font-weight:800;color:#0f172a;">All Cafe Orders</h1>
        <p style="color:#64748b;">Review and filter past and live customer orders.</p>
      </div>

      <div style="display:flex;gap:8px;">
        <a href="orders.php?status=all" class="btn btn-sm <?= ($statusFilter === 'all') ? 'btn-dark' : 'btn-outline' ?>" style="color:<?= ($statusFilter === 'all') ? '#fff' : '#0f172a' ?>;border-color:#cbd5e1;">All</a>
        <a href="orders.php?status=placed" class="btn btn-sm <?= ($statusFilter === 'placed') ? 'btn-dark' : 'btn-outline' ?>" style="color:<?= ($statusFilter === 'placed') ? '#fff' : '#0f172a' ?>;border-color:#cbd5e1;">Placed</a>
        <a href="orders.php?status=preparing" class="btn btn-sm <?= ($statusFilter === 'preparing') ? 'btn-dark' : 'btn-outline' ?>" style="color:<?= ($statusFilter === 'preparing') ? '#fff' : '#0f172a' ?>;border-color:#cbd5e1;">Preparing</a>
        <a href="orders.php?status=served" class="btn btn-sm <?= ($statusFilter === 'served') ? 'btn-dark' : 'btn-outline' ?>" style="color:<?= ($statusFilter === 'served') ? '#fff' : '#0f172a' ?>;border-color:#cbd5e1;">Served</a>
      </div>
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
            <th>Items Ordered</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr><td colspan="7" style="text-align:center;padding:2rem;color:#64748b;">No matching orders found.</td></tr>
          <?php else: ?>
            <?php foreach ($orders as $ord): 
              $itStmt = $db->prepare("SELECT oi.quantity, m.item_name FROM order_items oi JOIN menu_items m ON oi.item_id = m.item_id WHERE oi.order_id = ?");
              $itStmt->execute([$ord['order_id']]);
              $lineItems = $itStmt->fetchAll();
            ?>
              <tr>
                <td style="font-weight:700;">#<?= $ord['order_id'] ?></td>
                <td><span class="table-badge">Table <?= htmlspecialchars($ord['table_number']) ?></span></td>
                <td><?= htmlspecialchars($ord['customer_name']) ?></td>
                <td style="color:#64748b;font-size:0.85rem;"><?= date('M d, Y h:i A', strtotime($ord['order_date'])) ?></td>
                <td style="font-weight:800;color:#16a34a;">₹<?= number_format($ord['total_amount'], 2) ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($ord['order_status']) ?>">
                    <?= htmlspecialchars($ord['order_status']) ?>
                  </span>
                </td>
                <td style="font-size:0.85rem;color:#475569;">
                  <?php foreach ($lineItems as $li): ?>
                    <?= htmlspecialchars($li['item_name']) ?> (×<?= $li['quantity'] ?>), 
                  <?php endforeach; ?>
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
