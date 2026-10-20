<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

$error = null;
$success = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $tableNum = trim($_POST['table_number'] ?? '');
    $custName = trim($_POST['customer_name'] ?? 'Guest');
    $notes = trim($_POST['notes'] ?? '');
    $cartJson = $_POST['cart_data'] ?? '[]';
    $cartItems = json_decode($cartJson, true);

    if (empty($tableNum)) {
        $error = "Please specify your Table Number.";
    } elseif (empty($cartItems) || !is_array($cartItems)) {
        $error = "Your cart is empty. Please add items before placing an order.";
    } else {
        try {
            $db->beginTransaction();

            // Calculate total
            $totalAmount = 0;
            foreach ($cartItems as $ci) {
                $totalAmount += floatval($ci['price']) * intval($ci['qty']);
            }

            // Insert into cafe_orders
            $stmt = $db->prepare("
                INSERT INTO cafe_orders (table_number, customer_name, total_amount, order_status, notes)
                VALUES (?, ?, ?, 'PLACED', ?)
            ");
            $stmt->execute([$tableNum, $custName, $totalAmount, $notes]);
            $orderId = $db->lastInsertId();

            // Insert into order_items
            $itemStmt = $db->prepare("
                INSERT INTO order_items (order_id, item_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItems as $ci) {
                $itemStmt->execute([$orderId, intval($ci['id']), intval($ci['qty']), floatval($ci['price'])]);
            }

            // Also log to audit_log (Demonstrates Practical 10 auditing)
            $auditStmt = $db->prepare("
                INSERT INTO audit_log (table_name, operation, details)
                VALUES ('CAFE_ORDERS', 'INSERT', ?)
            ");
            $auditStmt->execute(["New order #{$orderId} placed for Table {$tableNum} (Total: ₹{$totalAmount})"]);

            $db->commit();

            // Redirect to live order tracking
            echo "<script>
                    localStorage.removeItem('qr_cafe_cart');
                    window.location.href = 'track_order.php?order_id=" . $orderId . "';
                  </script>";
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Failed to place order: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Review & Place Order — QR Cafe';
require_once __DIR__ . '/includes/header.php';

$tables = $db->query("SELECT table_number, capacity FROM cafe_tables WHERE table_status = 'AVAILABLE' ORDER BY table_number")->fetchAll();
?>

<div style="max-width: 850px; margin: 0 auto;">
  <div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary);">Your Order Summary</h1>
    <p style="color: var(--text-muted);">Review your selected items and provide your table details.</p>
  </div>

  <?php if ($error): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:1rem;border-radius:var(--radius-sm);margin-bottom:1.5rem;font-weight:600;">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="checkout.php" id="checkoutForm">
    <input type="hidden" name="cart_data" id="cartDataInput" value="[]">

    <!-- Cart items table -->
    <div class="table-responsive" style="margin-bottom: 2rem;">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Unit Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="cartItemsList">
          <!-- Populated by assets/js/main.js -->
        </tbody>
        <tfoot>
          <tr style="background:#fdfaf6;border-top:2px solid var(--border);">
            <td colspan="3" style="text-align:right;font-weight:800;font-size:1.1rem;color:var(--primary);">Grand Total:</td>
            <td id="cartTotalAmount" style="font-weight:800;font-size:1.2rem;color:var(--secondary);">₹0.00</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Dining details -->
    <div class="form-card" style="max-width: 100%;">
      <h3 style="margin-bottom: 1.25rem; font-size: 1.2rem; color: var(--primary);">Table & Guest Details</h3>
      
      <div class="form-grid">
        <div class="form-group">
          <label>Cafe Table Number *</label>
          <select name="table_number" class="form-control" required>
            <option value="">-- Select Your Table --</option>
            <?php foreach ($tables as $t): ?>
              <option value="<?= htmlspecialchars($t['table_number']) ?>" 
                      <?= ($currentTable === $t['table_number']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['table_number']) ?> (<?= $t['capacity'] ?> Seats)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Your Name (Optional)</label>
          <input type="text" name="customer_name" class="form-control" placeholder="e.g. Rahul Sharma" value="Guest">
        </div>

        <div class="form-group full">
          <label>Special Instructions / Dietary Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Less spicy, sugar-free coffee, extra ketchup..."></textarea>
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;">
        <button type="button" class="btn btn-outline" style="color:var(--text-muted);border-color:var(--border);" onclick="CafeApp.clearCart()">
          🗑️ Clear Cart
        </button>
        <button type="submit" class="btn btn-primary" style="font-size:1.05rem;padding:0.9rem 2rem;">
          🚀 Confirm & Send to Kitchen
        </button>
      </div>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
