<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();

$msg = null;

// Add item
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['item_name']);
    $price = floatval($_POST['price']);
    $cat = trim($_POST['category']);
    $desc = trim($_POST['description']);

    if (!empty($name) && $price >= 0) {
        $stmt = $db->prepare("INSERT INTO menu_items (item_name, price, category, description, availability) VALUES (?, ?, ?, ?, 'Y')");
        $stmt->execute([$name, $price, $cat, $desc]);
        $msg = "Item '{$name}' added successfully!";
    }
}

// Toggle availability
if (isset($_GET['toggle'])) {
    $itemId = intval($_GET['toggle']);
    $curr = $db->query("SELECT availability FROM menu_items WHERE item_id = {$itemId}")->fetchColumn();
    $new = ($curr === 'Y') ? 'N' : 'Y';
    $db->query("UPDATE menu_items SET availability = '{$new}' WHERE item_id = {$itemId}");
    header("Location: menu_manage.php");
    exit;
}

// Delete item
if (isset($_GET['delete'])) {
    $itemId = intval($_GET['delete']);
    $db->query("DELETE FROM menu_items WHERE item_id = {$itemId}");
    header("Location: menu_manage.php");
    exit;
}

$items = $db->query("SELECT * FROM menu_items ORDER BY category, item_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Menu — QR Cafe Admin</title>
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
        <li><a href="bookings.php" style="color:#cbd5e1;">Bookings</a></li>
        <li><a href="menu_manage.php" class="active" style="color:#fff;">Manage Menu</a></li>
        <li><a href="db_viewer.php" style="color:#60a5fa;">DB Inspector</a></li>
      </ul>
    </div>
  </header>

  <main class="main-wrapper">
    <div style="margin-bottom:2rem;">
      <h1 style="font-size:2.2rem;font-weight:800;color:#0f172a;">Menu Management (CRUD)</h1>
      <p style="color:#64748b;">Add new cafe items, adjust prices, and toggle in-stock availability.</p>
    </div>

    <?php if ($msg): ?>
      <div style="background:#dcfce7;color:#15803d;padding:1rem;border-radius:var(--radius-sm);margin-bottom:1.5rem;font-weight:700;">
        ✅ <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <!-- Add Item Form -->
    <div class="form-card" style="max-width:100%;margin-bottom:2.5rem;">
      <h3 style="font-size:1.2rem;color:var(--primary);margin-bottom:1rem;">Add New Cafe Item</h3>
      <form method="POST" action="menu_manage.php">
        <input type="hidden" name="action" value="add">
        <div class="form-grid">
          <div class="form-group">
            <label>Item Name *</label>
            <input type="text" name="item_name" class="form-control" placeholder="e.g. Vanilla Iced Latte" required>
          </div>
          <div class="form-group">
            <label>Price (₹) *</label>
            <input type="number" step="0.5" name="price" class="form-control" placeholder="150.00" required>
          </div>
          <div class="form-group">
            <label>Category *</label>
            <select name="category" class="form-control" required>
              <option value="Beverages">Beverages</option>
              <option value="Food">Food / Snacks</option>
              <option value="Desserts">Desserts</option>
            </select>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" class="form-control" placeholder="Brief description of flavors...">
          </div>
        </div>
        <button type="submit" class="btn btn-primary">+ Add Item to Menu</button>
      </form>
    </div>

    <!-- Items Table -->
    <div class="table-responsive">
      <table class="custom-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Item Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Availability</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td>#<?= $it['item_id'] ?></td>
              <td style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($it['item_name']) ?></td>
              <td><span class="menu-badge" style="position:static;"><?= htmlspecialchars($it['category']) ?></span></td>
              <td style="font-weight:800;color:#16a34a;">₹<?= number_format($it['price'], 2) ?></td>
              <td>
                <a href="menu_manage.php?toggle=<?= $it['item_id'] ?>" class="btn btn-sm" style="padding:4px 10px;font-size:0.75rem;background:<?= ($it['availability'] === 'Y') ? '#dcfce7;color:#15803d;' : '#fee2e2;color:#b91c1c;' ?>">
                  <?= ($it['availability'] === 'Y') ? '🟢 In Stock' : '🔴 Out of Stock' ?>
                </a>
              </td>
              <td>
                <a href="menu_manage.php?delete=<?= $it['item_id'] ?>" onclick="return confirm('Delete this menu item?');" style="color:#ef4444;font-size:0.9rem;font-weight:600;">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
