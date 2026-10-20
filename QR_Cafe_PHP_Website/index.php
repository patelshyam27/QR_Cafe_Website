<?php
require_once __DIR__ . '/config/db.php';
$pageTitle = 'QR Cafe — Smart Dining Experience';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$featuredItems = $db->query("SELECT * FROM menu_items WHERE availability = 'Y' LIMIT 4")->fetchAll();
?>

<!-- Hero Section -->
<section class="hero">
  <span class="hero-tag">Smart QR Dining Experience</span>
  <h1>Order fresh from your table with a simple scan.</h1>
  <p>Skip the waiting queue! Scan the QR code on your cafe table to browse the menu, customize your order, and track kitchen preparation in real-time.</p>
  <div class="hero-actions">
    <a href="menu.php" class="btn btn-primary">📖 Explore Digital Menu</a>
    <a href="book.php" class="btn btn-outline">🪑 Reserve a Table</a>
    <a href="qr_generator.php" class="btn btn-outline">📱 Simulate Table QR</a>
  </div>
</section>

<!-- How It Works Section -->
<section style="margin-bottom: 3.5rem;">
  <div style="text-align: center; margin-bottom: 2.5rem;">
    <p style="font-weight: 800; color: var(--secondary); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Seamless 3-Step Process</p>
    <h2 style="font-size: 2rem; color: var(--primary); font-weight: 800;">How Smart QR Dining Works</h2>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; text-align: center; box-shadow: var(--shadow-sm);">
      <div style="font-size: 2.8rem; margin-bottom: 1rem;">📱</div>
      <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">1. Scan Table QR</h3>
      <p style="color: var(--text-muted); font-size: 0.9rem;">Every table has a unique QR code. Simply scan with your phone camera to open the menu for your specific table.</p>
    </div>

    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; text-align: center; box-shadow: var(--shadow-sm);">
      <div style="font-size: 2.8rem; margin-bottom: 1rem;">☕</div>
      <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">2. Pick & Customize</h3>
      <p style="color: var(--text-muted); font-size: 0.9rem;">Browse handcrafted beverages and gourmet snacks. Add items to your cart with your personal cooking instructions.</p>
    </div>

    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; text-align: center; box-shadow: var(--shadow-sm);">
      <div style="font-size: 2.8rem; margin-bottom: 1rem;">👨‍🍳</div>
      <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">3. Freshly Prepared</h3>
      <p style="color: var(--text-muted); font-size: 0.9rem;">Your order pings directly to the kitchen display screen. Track its preparation status live until served at your table.</p>
    </div>
  </div>
</section>

<!-- Featured Menu Items -->
<section>
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
    <div>
      <p style="font-weight: 800; color: var(--secondary); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Customer Favorites</p>
      <h2 style="font-size: 2rem; color: var(--primary); font-weight: 800;">Featured Items</h2>
    </div>
    <a href="menu.php" class="btn btn-sm btn-dark">View Full Menu ➔</a>
  </div>

  <div class="menu-grid">
    <?php foreach ($featuredItems as $item): ?>
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
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
