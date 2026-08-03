<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Dashboard';

$totalStudents  = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$availableRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 'Available'")->fetchColumn();
$occupiedRooms  = $pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 'Full'")->fetchColumn();
$totalPayments  = $pdo->query("SELECT COALESCE(SUM(Amount),0) FROM payments WHERE PaymentStatus = 'Paid'")->fetchColumn();

$recentAllocations = $pdo->query(
    "SELECT a.AllocationID, s.FullName, r.RoomNumber, a.CheckInDate, a.AllocationStatus
     FROM allocations a
     JOIN students s ON s.StudentID = a.StudentID
     JOIN rooms r ON r.RoomID = a.RoomID
     ORDER BY a.AllocationID DESC LIMIT 5"
)->fetchAll();

$pendingPayments = $pdo->query(
    "SELECT p.PaymentID, s.FullName, p.PaymentMonth, p.Amount, p.PaymentStatus
     FROM payments p
     JOIN allocations a ON a.AllocationID = p.AllocationID
     JOIN students s ON s.StudentID = a.StudentID
     WHERE p.PaymentStatus IN ('Pending','Overdue')
     ORDER BY p.PaymentID DESC LIMIT 5"
)->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
  <h3 class="fw-bold mb-4">Dashboard</h3>

  <?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= clean($flash['type']) ?> alert-dismissible fade show alert-auto-dismiss" role="alert">
      <?= clean($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="kpi-card kpi-students">
        <i class="fa-solid fa-user-graduate kpi-icon"></i>
        <div class="mt-2 small text-uppercase">Total Students</div>
        <h3><?= (int)$totalStudents ?></h3>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card kpi-rooms">
        <i class="fa-solid fa-door-open kpi-icon"></i>
        <div class="mt-2 small text-uppercase">Available Rooms</div>
        <h3><?= (int)$availableRooms ?></h3>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card kpi-occupied">
        <i class="fa-solid fa-bed kpi-icon"></i>
        <div class="mt-2 small text-uppercase">Occupied (Full) Rooms</div>
        <h3><?= (int)$occupiedRooms ?></h3>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card kpi-payments">
        <i class="fa-solid fa-money-bill-wave kpi-icon"></i>
        <div class="mt-2 small text-uppercase">Total Payments Collected</div>
        <h3><?= formatMoney($totalPayments) ?></h3>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><i class="fa-solid fa-clock-rotate-left me-2"></i>Recent Allocations</div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <thead><tr><th>Student</th><th>Room</th><th>Check-In</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!$recentAllocations): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No allocations yet</td></tr>
              <?php endif; ?>
              <?php foreach ($recentAllocations as $a): ?>
              <tr>
                <td><?= clean($a['FullName']) ?></td>
                <td><?= clean($a['RoomNumber']) ?></td>
                <td><?= formatDate($a['CheckInDate']) ?></td>
                <td><?= statusBadge($a['AllocationStatus']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><i class="fa-solid fa-triangle-exclamation me-2"></i>Pending / Overdue Payments</div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <thead><tr><th>Student</th><th>Month</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!$pendingPayments): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No pending payments</td></tr>
              <?php endif; ?>
              <?php foreach ($pendingPayments as $p): ?>
              <tr>
                <td><?= clean($p['FullName']) ?></td>
                <td><?= clean($p['PaymentMonth']) ?></td>
                <td><?= formatMoney($p['Amount']) ?></td>
                <td><?= statusBadge($p['PaymentStatus']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
