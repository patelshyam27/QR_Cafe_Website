<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-detect and remember table from QR code scan
if (isset($_GET['table']) && !empty($_GET['table'])) {
    $_SESSION['table_number'] = htmlspecialchars(trim($_GET['table']));
}
$currentTable = $_SESSION['table_number'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'QR Cafe — Smart Dining Experience' ?></title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <!-- Advanced CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- Top Navigation Bar -->
  <header class="navbar">
    <div class="nav-container">
      <a class="brand" href="index.php">
        <span class="brand-icon">☕</span>
        <span>QR Cafe</span>
      </a>

      <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="menu.php">Digital Menu</a></li>
        <li><a href="book.php">Reserve Table</a></li>
        <li><a href="checkout.php">My Order</a></li>
        <li><a href="qr_generator.php">Table QRs</a></li>
        <li><a href="admin/index.php" style="color:var(--secondary);font-weight:700;">Admin</a></li>
      </ul>

      <div style="display:flex;align-items:center;gap:12px;">
        <?php if ($currentTable): ?>
          <span class="table-badge">📍 Table <?= htmlspecialchars($currentTable) ?></span>
        <?php else: ?>
          <a href="qr_generator.php" class="btn btn-sm btn-dark" style="font-size:0.8rem;">Scan QR</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="main-wrapper">
