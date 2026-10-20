<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$stmt = $db->prepare("SELECT * FROM cafe_orders WHERE order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die("<div style='padding:40px;text-align:center;font-family:sans-serif;'><h2>Order Not Found</h2><p>Please place an order from the menu.</p><a href='menu.php'>Go to Menu</a></div>");
}

// Fetch line items
$itemStmt = $db->prepare("
    SELECT oi.*, m.item_name 
    FROM order_items oi
    JOIN menu_items m ON oi.item_id = m.item_id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();

$pageTitle = "Track Order #{$orderId} — QR Cafe";
require_once __DIR__ . '/includes/header.php';

// Map status to progress step (1 to 3)
$status = strtoupper($order['order_status']);
$stepIndex = 1;
if ($status === 'PREPARING') $stepIndex = 2;
elseif ($status === 'SERVED' || $status === 'COMPLETED') $stepIndex = 3;
?>

<div style="max-width: 800px; margin: 0 auto; text-align: center;">
  <span class="table-badge" style="margin-bottom: 0.5rem;">Table: <?= htmlspecialchars($order['table_number']) ?></span>
  <h1 style="font-size: 2.4rem; font-weight: 800; color: var(--primary);">Order #<?= $orderId ?> Placed!</h1>
  <p style="color: var(--text-muted);">The kitchen is now preparing your delicious food and drinks.</p>

  <!-- 3-Stage Progress Tracker -->
  <div class="tracker-steps">
    <div class="step-item <?= ($stepIndex >= 1) ? 'active completed' : '' ?>">
      <div class="step-bubble">📝</div>
      <p style="font-weight: 700; font-size: 0.95rem;">Order Placed</p>
      <span style="font-size: 0.8rem; color: var(--text-muted);"><?= date('h:i A', strtotime($order['order_date'])) ?></span>
    </div>

    <div class="step-item <?= ($stepIndex >= 2) ? 'active completed' : '' ?> <?= ($stepIndex == 2) ? 'active' : '' ?>">
      <div class="step-bubble">👨‍🍳</div>
      <p style="font-weight: 700; font-size: 0.95rem;">Preparing in Kitchen</p>
      <span style="font-size: 0.8rem; color: var(--text-muted);">Chef is cooking</span>
    </div>

    <div class="step-item <?= ($stepIndex >= 3) ? 'active completed' : '' ?>">
      <div class="step-bubble">🍽️</div>
      <p style="font-weight: 700; font-size: 0.95rem;">Served at Table</p>
      <span style="font-size: 0.8rem; color: var(--text-muted);">Enjoy your meal!</span>
    </div>
  </div>

  <!-- Order Receipt Card -->
  <div class="form-card" style="text-align: left; margin-bottom: 2rem;">
    <h3 style="margin-bottom: 1rem; color: var(--primary); font-size: 1.15rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
      Bill & Item Summary
    </h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 0.95rem;">
      <?php foreach ($orderItems as $item): ?>
        <tr style="border-bottom: 1px dashed #e2e8f0;">
          <td style="padding: 0.6rem 0;"><?= htmlspecialchars($item['item_name']) ?> × <?= $item['quantity'] ?></td>
          <td style="padding: 0.6rem 0; text-align: right; font-weight: 700;">₹<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr style="font-size: 1.15rem; font-weight: 800; color: var(--primary);">
        <td style="padding-top: 1rem;">Total Paid / Due:</td>
        <td style="padding-top: 1rem; text-align: right; color: var(--secondary);">₹<?= number_format($order['total_amount'], 2) ?></td>
      </tr>
    </table>

    <?php if (!empty($order['notes'])): ?>
      <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted); background: #fdfaf6; padding: 0.5rem; border-radius: var(--radius-sm);">
        <strong>Your Notes:</strong> <?= htmlspecialchars($order['notes']) ?>
      </p>
    <?php endif; ?>
  </div>

  <div style="display: flex; gap: 1rem; justify-content: center;">
    <button type="button" class="btn btn-dark" onclick="window.location.reload();">🔄 Refresh Status</button>
    <a href="menu.php" class="btn btn-primary">+ Order More Items</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
