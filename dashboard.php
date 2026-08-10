<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Dashboard';

// Fetch summary KPIs for top stat cards
$totalStudents  = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$availableRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 'Available'")->fetchColumn();
$occupiedRooms  = $pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 'Full'")->fetchColumn();
$totalPayments  = $pdo->query("SELECT COALESCE(SUM(Amount),0) FROM payments WHERE PaymentStatus = 'Paid'")->fetchColumn();

// Fetch 5 most recent student room allocations
$recentAllocations = $pdo->query(
    "SELECT a.AllocationID, s.FullName, r.RoomNumber, a.CheckInDate, a.AllocationStatus
     FROM allocations a
     JOIN students s ON s.StudentID = a.StudentID
     JOIN rooms r ON r.RoomID = a.RoomID
     ORDER BY a.AllocationID DESC LIMIT 5"
)->fetchAll();

// Fetch top 5 pending or overdue payments needing attention
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

  <!-- Page Title + Quick Action button -->
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h3 class="fw-bold mb-0">Dashboard</h3>
    <a href="allocations.php?action=new" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus me-1"></i> New Allocation
    </a>
  </div>

  <?php if ($flash = getFlash()): ?>
    <!-- Flash notification message -->
    <div class="alert alert-<?= clean($flash['type']) ?> alert-dismissible fade show alert-auto-dismiss" role="alert">
      <?= clean($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Summary Stats Cards (KPI overview) -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="kpi-card kpi-students">
        <i class="fa-solid fa-user-graduate kpi-icon"></i>
        <div class="kpi-label">Total Students</div>
        <div class="kpi-value"><?= (int)$totalStudents ?></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card kpi-rooms">
        <i class="fa-solid fa-door-open kpi-icon"></i>
        <div class="kpi-label">Available Rooms</div>
        <div class="kpi-value"><?= (int)$availableRooms ?></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card kpi-occupied">
        <i class="fa-solid fa-bed kpi-icon"></i>
        <div class="kpi-label">Occupied Rooms</div>
        <div class="kpi-value"><?= (int)$occupiedRooms ?></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card kpi-payments">
        <i class="fa-solid fa-wallet kpi-icon"></i>
        <div class="kpi-label">Payments Collected</div>
        <div class="kpi-value"><?= formatMoney($totalPayments) ?></div>
      </div>
    </div>
  </div>

  <!-- Recent Tables grid layout -->
  <div class="row g-3 align-items-start">
    <!-- Recent Allocations table card -->
    <div class="col-lg-6">
      <div class="card dashboard-table-card mb-0">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span><i class="fa-solid fa-clock-rotate-left me-2"></i>Recent Allocations</span>
          <a href="allocations.php" class="card-header-link">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
        <div class="card-body p-0">
          <table class="table dashboard-table mb-0">
            <thead><tr><th>Student</th><th>Room</th><th>Check-In</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!$recentAllocations): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">
                  <i class="fa-solid fa-inbox fa-lg mb-2 d-block opacity-40"></i>No allocations yet
                </td></tr>
              <?php endif; ?>
              <?php foreach ($recentAllocations as $a): ?>
              <tr>
                <td title="<?= clean($a['FullName']) ?>"><?= clean($a['FullName']) ?></td>
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

    <!-- Pending / Overdue Payments table card -->
    <div class="col-lg-6">
      <div class="card dashboard-table-card mb-0">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span><i class="fa-solid fa-triangle-exclamation me-2"></i>Pending / Overdue Payments</span>
          <a href="payments.php" class="card-header-link">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
        <div class="card-body p-0">
          <table class="table dashboard-table mb-0">
            <!-- NOTE: Right-aligned Amount header for currency standard -->
            <thead><tr><th>Student</th><th>Month</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!$pendingPayments): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">
                  <i class="fa-solid fa-circle-check fa-lg mb-2 d-block opacity-40 text-success"></i>No pending payments
                </td></tr>
              <?php endif; ?>
              <?php foreach ($pendingPayments as $p): ?>
              <tr>
                <td title="<?= clean($p['FullName']) ?>"><?= clean($p['FullName']) ?></td>
                <td><?= formatMonth($p['PaymentMonth']) ?></td>
                <td class="text-end fw-600"><?= formatMoney($p['Amount']) ?></td>
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
