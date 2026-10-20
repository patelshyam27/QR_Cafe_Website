<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();
$pageTitle = 'Table QR Standee Generator — QR Cafe';
require_once __DIR__ . '/includes/header.php';

$tables = $db->query("SELECT * FROM cafe_tables ORDER BY table_number")->fetchAll();

// Determine base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$baseDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
$baseUrl = "{$protocol}://{$host}{$baseDir}";
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary);">Table QR Standees</h1>
    <p style="color: var(--text-muted);">Print these QR standee cards to place on physical cafe tables or click to simulate a customer scanning them.</p>
  </div>
  <button type="button" class="btn btn-dark" onclick="window.print()">
    🖨️ Print QR Standees
  </button>
</div>

<!-- QR Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 2rem;">
  <?php foreach ($tables as $t): 
    $menuUrl = "{$baseUrl}/menu.php?table=" . urlencode($t['table_number']);
    $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($menuUrl);
  ?>
    <div style="background: #fff; border: 2px solid var(--border); border-radius: var(--radius-lg); padding: 1.75rem; text-align: center; box-shadow: var(--shadow-sm); position: relative;">
      <span style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--secondary); letter-spacing: 1px;">Smart Dine</span>
      <h2 style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin: 0.25rem 0 1rem;">
        Table <?= htmlspecialchars($t['table_number']) ?>
      </h2>

      <div style="background: #fafafa; padding: 1rem; border-radius: var(--radius-md); display: inline-block; border: 1px solid #f1f5f9; margin-bottom: 1rem;">
        <img src="<?= $qrApiUrl ?>" alt="QR for Table <?= htmlspecialchars($t['table_number']) ?>" style="width: 160px; height: 160px; margin: 0 auto;">
      </div>

      <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
        Capacity: <strong><?= $t['capacity'] ?> Guests</strong>
      </p>

      <a href="menu.php?table=<?= urlencode($t['table_number']) ?>" class="btn btn-sm btn-primary" style="width: 100%;">
        📱 Test / Simulate Scan
      </a>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
