<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();

// Handle cancellation
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['booking_id'], $_POST['action'])) {
    $bId = intval($_POST['booking_id']);
    $act = $_POST['action'];
    $newStatus = ($act === 'cancel') ? 'CANCELLED' : 'COMPLETED';

    $stmt = $db->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
    $stmt->execute([$newStatus, $bId]);
    header("Location: bookings.php");
    exit;
}

// SQL JOIN: Customers + Bookings + Cafe Tables (Practical 5)
$bookings = $db->query("
    SELECT 
        b.booking_id,
        c.customer_name,
        c.phone,
        c.age,
        t.table_number,
        t.capacity,
        b.booking_date,
        b.start_time,
        b.end_time,
        b.booking_status
    FROM bookings b
    JOIN customers c ON b.customer_id = c.customer_id
    JOIN cafe_tables t ON b.table_id = t.table_id
    ORDER BY b.booking_date DESC, b.start_time ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Bookings — QR Cafe Admin</title>
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
        <li><a href="orders.php" style="color:#cbd5e1;">Orders</a></li>
        <li><a href="bookings.php" class="active" style="color:#fff;">Bookings</a></li>
        <li><a href="menu_manage.php" style="color:#cbd5e1;">Manage Menu</a></li>
        <li><a href="db_viewer.php" style="color:#60a5fa;">DB Inspector</a></li>
      </ul>
    </div>
  </header>

  <main class="main-wrapper">
    <div style="margin-bottom:2rem;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h1 style="font-size:2.2rem;font-weight:800;color:#0f172a;">Table Reservations Report</h1>
        <p style="color:#64748b;">Customer advance table reservations (SQL Multi-Table JOIN).</p>
      </div>
      <a href="../book.php" target="_blank" class="btn btn-primary">+ New Booking Form</a>
    </div>

    <div class="table-responsive">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Booking #</th>
            <th>Customer Name</th>
            <th>Phone</th>
            <th>Age</th>
            <th>Table</th>
            <th>Date</th>
            <th>Time Slot</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bookings)): ?>
            <tr><td colspan="9" style="text-align:center;padding:2rem;color:#64748b;">No table reservations yet.</td></tr>
          <?php else: ?>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td style="font-weight:700;">#<?= $b['booking_id'] ?></td>
                <td style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($b['customer_name']) ?></td>
                <td><?= htmlspecialchars($b['phone']) ?></td>
                <td><?= $b['age'] ?> yrs</td>
                <td><span class="table-badge">Table <?= htmlspecialchars($b['table_number']) ?> (<?= $b['capacity'] ?>p)</span></td>
                <td><?= htmlspecialchars($b['booking_date']) ?></td>
                <td><?= htmlspecialchars($b['start_time']) ?> – <?= htmlspecialchars($b['end_time']) ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($b['booking_status']) ?>">
                    <?= htmlspecialchars($b['booking_status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($b['booking_status'] === 'CONFIRMED'): ?>
                    <form method="POST" action="bookings.php" style="display:inline-block;" onsubmit="return confirm('Cancel this booking?');">
                      <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                      <input type="hidden" name="action" value="cancel">
                      <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#b91c1c;">Cancel</button>
                    </form>
                  <?php else: ?>
                    <span style="color:#94a3b8;font-size:0.85rem;">Completed</span>
                  <?php endif; ?>
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
