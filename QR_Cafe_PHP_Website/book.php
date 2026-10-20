<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

$error = null;
$success = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $custName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $age = intval($_POST['age'] ?? 18);
    $email = trim($_POST['email'] ?? '');
    $tableId = intval($_POST['table_id'] ?? 0);
    $bDate = trim($_POST['booking_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');

    if (empty($custName) || empty($phone) || empty($tableId) || empty($bDate) || empty($startTime) || empty($endTime)) {
        $error = "Please fill in all required fields.";
    } elseif ($startTime >= $endTime) {
        $error = "End time must be later than start time.";
    } elseif ($age < 18) {
        $error = "Customer must be at least 18 years old to make a cafe table reservation (Trigger rule).";
    } else {
        try {
            // Check for overlapping confirmed booking for same table & date
            $overlapStmt = $db->prepare("
                SELECT COUNT(*) FROM bookings
                WHERE table_id = ?
                  AND booking_date = ?
                  AND booking_status = 'CONFIRMED'
                  AND start_time < ?
                  AND end_time > ?
            ");
            $overlapStmt->execute([$tableId, $bDate, $endTime, $startTime]);
            if ($overlapStmt->fetchColumn() > 0) {
                $error = "This table already has a confirmed booking during the selected time slot. Please choose another table or time.";
            } else {
                $db->beginTransaction();

                // Find or insert customer
                $cStmt = $db->prepare("SELECT customer_id FROM customers WHERE phone = ?");
                $cStmt->execute([$phone]);
                $custId = $cStmt->fetchColumn();

                if (!$custId) {
                    $insC = $db->prepare("INSERT INTO customers (customer_name, phone, age, email) VALUES (?, ?, ?, ?)");
                    $insC->execute([$custName, $phone, $age, $email]);
                    $custId = $db->lastInsertId();
                } else {
                    $updC = $db->prepare("UPDATE customers SET customer_name = ?, age = ?, email = ? WHERE customer_id = ?");
                    $updC->execute([$custName, $age, $email, $custId]);
                }

                // Insert booking
                $bStmt = $db->prepare("
                    INSERT INTO bookings (table_id, customer_id, booking_date, start_time, end_time, booking_status)
                    VALUES (?, ?, ?, ?, ?, 'CONFIRMED')
                ");
                $bStmt->execute([$tableId, $custId, $bDate, $startTime, $endTime]);
                $bookingId = $db->lastInsertId();

                $db->commit();
                $success = "Table reserved successfully! Your Booking Reference ID is #{$bookingId}.";
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = "Reservation failed: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Reserve a Table — QR Cafe';
require_once __DIR__ . '/includes/header.php';

$tables = $db->query("SELECT * FROM cafe_tables WHERE table_status = 'AVAILABLE' ORDER BY table_number")->fetchAll();
$today = date('Y-m-d');
?>

<div style="max-width: 680px; margin: 0 auto;">
  <div style="text-align: center; margin-bottom: 2rem;">
    <p style="font-weight: 800; color: var(--secondary); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Table Reservation</p>
    <h1 style="font-size: 2.3rem; font-weight: 800; color: var(--primary);">Book Your Cafe Table</h1>
    <p style="color: var(--text-muted);">Reserve a table in advance for meetings, dates, or study sessions.</p>
  </div>

  <?php if ($error): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:1rem;border-radius:var(--radius-sm);margin-bottom:1.5rem;font-weight:600;">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div style="background:#dcfce7;color:#15803d;padding:1.25rem;border-radius:var(--radius-sm);margin-bottom:1.5rem;font-weight:700;text-align:center;">
      🎉 <?= htmlspecialchars($success) ?>
      <div style="margin-top:0.75rem;">
        <a href="admin/bookings.php" class="btn btn-sm btn-dark">View in Admin Bookings</a>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" action="book.php" class="form-card">
    <div class="form-grid">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="customer_name" class="form-control" placeholder="e.g. Priya Shah" required>
      </div>

      <div class="form-group">
        <label>Phone Number *</label>
        <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile number" required>
      </div>

      <div class="form-group">
        <label>Age *</label>
        <input type="number" name="age" class="form-control" value="20" min="12" max="100" required>
      </div>

      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="priya@example.com">
      </div>

      <div class="form-group full">
        <label>Select Table *</label>
        <select name="table_id" class="form-control" required>
          <option value="">-- Choose an Available Table --</option>
          <?php foreach ($tables as $t): ?>
            <option value="<?= $t['table_id'] ?>">
              Table <?= htmlspecialchars($t['table_number']) ?> (Capacity: <?= $t['capacity'] ?> Persons)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Booking Date *</label>
        <input type="date" name="booking_date" class="form-control" min="<?= $today ?>" value="<?= $today ?>" required>
      </div>

      <div class="form-group">
        <label>Start Time (24h) *</label>
        <input type="time" name="start_time" class="form-control" value="15:00" required>
      </div>

      <div class="form-group full">
        <label>End Time (24h) *</label>
        <input type="time" name="end_time" class="form-control" value="16:30" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.05rem;">
      Confirm Table Booking ➔
    </button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
