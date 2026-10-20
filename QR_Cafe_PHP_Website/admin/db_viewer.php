<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();

// Fetch list of all tables
if (DB_TYPE === 'sqlite') {
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
} else {
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
}

$selectedTable = $_GET['table'] ?? ($tables[0] ?? '');
$columns = [];
$rows = [];

if (!empty($selectedTable) && in_array($selectedTable, $tables)) {
    $stmt = $db->query("SELECT * FROM {$selectedTable} LIMIT 100");
    $rows = $stmt->fetchAll();
    if (!empty($rows)) {
        $columns = array_keys($rows[0]);
    } else {
        // Get column names even if table is empty
        if (DB_TYPE === 'sqlite') {
            $colsStmt = $db->query("PRAGMA table_info({$selectedTable})");
            while ($c = $colsStmt->fetch()) {
                $columns[] = $c['name'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Database Inspector — QR Cafe Admin</title>
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
        <li><a href="menu_manage.php" style="color:#cbd5e1;">Manage Menu</a></li>
        <li><a href="db_viewer.php" class="active" style="color:#60a5fa;font-weight:700;">DB Inspector</a></li>
      </ul>
    </div>
  </header>

  <main class="main-wrapper">
    <div style="margin-bottom:1.5rem;">
      <span style="font-size:0.8rem;background:#e2e8f0;padding:2px 8px;border-radius:4px;font-weight:700;color:#475569;">
        Engine: <?= strtoupper(DB_TYPE) ?>
      </span>
      <h1 style="font-size:2.2rem;font-weight:800;color:#0f172a;margin-top:0.25rem;">Live Database Inspector</h1>
      <p style="color:#64748b;">Inspect live tables, records, constraints, and audit logs directly inside your web browser.</p>
    </div>

    <!-- Table selector buttons -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1.5rem;">
      <?php foreach ($tables as $t): 
        $tCount = $db->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
      ?>
        <a href="db_viewer.php?table=<?= urlencode($t) ?>" 
           style="padding:8px 16px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:6px;
                  <?= ($t === $selectedTable) ? 'background:#2563eb;color:#fff;' : 'background:#e2e8f0;color:#334155;' ?>">
          <span>📋 <?= htmlspecialchars($t) ?></span>
          <span style="background:rgba(0,0,0,0.15);padding:1px 6px;border-radius:9999px;font-size:11px;"><?= $tCount ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Data grid -->
    <div class="card" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;overflow-x:auto;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
        <h3 style="margin:0;font-size:1.15rem;color:#0f172a;">Table: <span style="color:#2563eb;"><?= htmlspecialchars($selectedTable) ?></span></h3>
        <span style="font-size:13px;color:#64748b;">Showing <?= count($rows) ?> record(s)</span>
      </div>

      <?php if (!empty($rows)): ?>
        <table style="width:100%;border-collapse:collapse;font-size:13.5px;text-align:left;">
          <thead>
            <tr style="background:#f1f5f9;border-bottom:2px solid #cbd5e1;">
              <?php foreach ($columns as $c): ?>
                <th style="padding:10px 14px;font-weight:700;color:#334155;"><?= htmlspecialchars($c) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr style="border-bottom:1px solid #f1f5f9;">
                <?php foreach ($columns as $c): ?>
                  <td style="padding:10px 14px;color:#1e293b;"><?= htmlspecialchars((string)($r[$c] ?? 'NULL')) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="padding:2rem;text-align:center;color:#64748b;">No records in table '<?= htmlspecialchars($selectedTable) ?>'.</p>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
