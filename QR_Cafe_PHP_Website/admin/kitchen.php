<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();

// Handle status update
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = trim($_POST['new_status']);
    if (in_array($newStatus, ['PLACED', 'PREPARING', 'SERVED', 'CANCELLED'])) {
        $stmt = $db->prepare("UPDATE cafe_orders SET order_status = ? WHERE order_id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        // Log in audit_log
        $audit = $db->prepare("INSERT INTO audit_log (table_name, operation, details) VALUES ('CAFE_ORDERS', 'UPDATE', ?)");
        $audit->execute(["Kitchen changed Order #{$orderId} status to {$newStatus}"]);
    }
    header("Location: kitchen.php");
    exit;
}

// Fetch active orders (PLACED & PREPARING)
$orders = $db->query("
    SELECT * FROM cafe_orders 
    WHERE order_status IN ('PLACED', 'PREPARING') 
    ORDER BY order_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kitchen Display System (KDS) — QR Cafe</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <meta http-equiv="refresh" content="15"> <!-- Auto refresh every 15s -->
</head>
<body style="background:#0f172a;color:#f8fafc;">

  <header class="navbar" style="background:#1e293b;border-bottom:1px solid #334155;">
    <div class="nav-container">
      <a class="brand" href="index.php" style="color:#f8fafc;">
        <span class="brand-icon">👨‍🍳</span>
        <span>Kitchen Live Display (KDS)</span>
      </a>
      <ul class="nav-links">
        <li><a href="index.php" style="color:#cbd5e1;">Dashboard</a></li>
        <li><a href="kitchen.php" class="active" style="color:#fff;">Kitchen KDS</a></li>
        <li><a href="orders.php" style="color:#cbd5e1;">All Orders</a></li>
      </ul>
      <span style="font-size:0.85rem;color:#94a3b8;">Auto-refreshing every 15s ⚡</span>
    </div>
  </header>

  <main class="main-wrapper" style="max-width:1400px;">
    <div style="margin-bottom:2rem;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h1 style="font-size:2rem;font-weight:800;color:#f8fafc;">Active Kitchen Orders (<?= count($orders) ?>)</h1>
        <p style="color:#94a3b8;">Prepare dishes in chronological order as received from customer table scans.</p>
      </div>
      <a href="kitchen.php" class="btn btn-sm btn-dark" style="border:1px solid #334155;">🔄 Refresh Now</a>
    </div>

    <?php if (empty($orders)): ?>
      <div style="background:#1e293b;border:1px dashed #334155;border-radius:var(--radius-lg);padding:4rem;text-align:center;">
        <p style="font-size:3rem;margin-bottom:0.5rem;">🍳</p>
        <h3 style="font-size:1.4rem;font-weight:700;color:#f8fafc;margin-bottom:0.5rem;">All Orders Cleared!</h3>
        <p style="color:#94a3b8;">No pending orders in the kitchen. New orders will appear here automatically.</p>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:1.5rem;">
        <?php foreach ($orders as $ord): 
          // Fetch order items
          $itemStmt = $db->prepare("
              SELECT oi.*, m.item_name 
              FROM order_items oi
              JOIN menu_items m ON oi.item_id = m.item_id
              WHERE oi.order_id = ?
          ");
          $itemStmt->execute([$ord['order_id']]);
          $items = $itemStmt->fetchAll();
        ?>
          <div style="background:#1e293b;border:2px solid <?= ($ord['order_status'] === 'PREPARING') ? '#f59e0b' : '#3b82f6' ?>;border-radius:var(--radius-md);padding:1.5rem;display:flex;flex-direction:column;justify-content:space-between;">
            <div>
              <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;border-bottom:1px solid #334155;padding-bottom:0.75rem;">
                <div>
                  <span style="background:#f59e0b;color:#000;font-size:0.75rem;font-weight:800;padding:2px 8px;border-radius:9999px;">
                    Order #<?= $ord['order_id'] ?>
                  </span>
                  <h2 style="font-size:1.6rem;font-weight:800;color:#f8fafc;margin-top:0.25rem;">
                    Table <?= htmlspecialchars($ord['table_number']) ?>
                  </h2>
                </div>
                <span style="font-size:0.8rem;color:#94a3b8;"><?= date('h:i A', strtotime($ord['order_date'])) ?></span>
              </div>

              <div style="margin-bottom:1.5rem;">
                <ul style="list-style:none;">
                  <?php foreach ($items as $it): ?>
                    <li style="display:flex;justify-content:space-between;padding:0.4rem 0;font-size:1.1rem;border-bottom:1px dashed #334155;">
                      <span style="color:#f8fafc;font-weight:700;"><?= htmlspecialchars($it['item_name']) ?></span>
                      <span style="color:#f59e0b;font-weight:800;font-size:1.2rem;">× <?= $it['quantity'] ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>

                <?php if (!empty($ord['notes'])): ?>
                  <p style="margin-top:0.75rem;background:#334155;color:#fef08a;padding:0.5rem;border-radius:var(--radius-sm);font-size:0.85rem;">
                    <strong>Note:</strong> <?= htmlspecialchars($ord['notes']) ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Action buttons -->
            <form method="POST" action="kitchen.php" style="display:flex;gap:8px;">
              <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
              <?php if ($ord['order_status'] === 'PLACED'): ?>
                <button type="submit" name="new_status" value="PREPARING" class="btn btn-sm btn-primary" style="flex:1;">
                  🔥 Start Cooking
                </button>
              <?php else: ?>
                <button type="submit" name="new_status" value="SERVED" class="btn btn-sm btn-dark" style="flex:1;background:#16a34a;">
                  ✅ Mark Served
                </button>
              <?php endif; ?>
              <button type="submit" name="new_status" value="CANCELLED" class="btn btn-sm" style="background:#ef4444;color:#fff;">
                ✕
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
