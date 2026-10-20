<?php
require_once __DIR__ . '/config/db.php';
$pageTitle = 'Digital Menu — QR Cafe';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$categories = $db->query("SELECT DISTINCT category FROM menu_items ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$items = $db->query("SELECT * FROM menu_items WHERE availability = 'Y' ORDER BY category, item_name")->fetchAll();
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
  <div>
    <?php if ($currentTable): ?>
      <span class="table-badge" style="margin-bottom: 0.5rem;">Ordering for Table: <?= htmlspecialchars($currentTable) ?></span>
    <?php endif; ?>
    <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary);">Digital Cafe Menu</h1>
    <p style="color: var(--text-muted);">Choose your favorite snacks and handcrafted coffees.</p>
  </div>

  <!-- Live Search Bar -->
  <div style="position: relative; width: 100%; max-width: 320px;">
    <input type="text" id="menuSearch" class="form-control" placeholder="🔍 Search coffee, pizza, etc..." style="padding-left: 1rem; border-radius: var(--radius-full);">
  </div>
</div>

<!-- Category Tabs -->
<div class="category-tabs">
  <button type="button" class="tab-btn active" data-category="all">✨ All Items (<?= count($items) ?>)</button>
  <?php foreach ($categories as $cat): ?>
    <button type="button" class="tab-btn" data-category="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
  <?php endforeach; ?>
</div>

<!-- Menu Grid -->
<div class="menu-grid">
  <?php foreach ($items as $item): ?>
    <div class="menu-card" data-category="<?= htmlspecialchars($item['category']) ?>">
      <span class="menu-badge"><?= htmlspecialchars($item['category']) ?></span>
      <div class="menu-info">
        <h3><?= htmlspecialchars($item['item_name']) ?></h3>
        <p><?= htmlspecialchars($item['description'] ?? 'Delicious cafe specialty prepared fresh to order.') ?></p>
      </div>
      <div class="menu-footer">
        <span class="menu-price">₹<?= number_format($item['price'], 2) ?></span>
        <button type="button" class="btn btn-sm btn-primary" onclick="CafeApp.addToCart(<?= $item['item_id'] ?>, '<?= addslashes($item['item_name']) ?>', <?= $item['price'] ?>)">
          + Add to Order
        </button>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
