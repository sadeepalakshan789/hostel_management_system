<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Reports';

$reportType = $_GET['type'] ?? 'students';

$students = $rooms = $allocations = $payments = [];
$paymentSummary = [];

if ($reportType === 'students') {
    $students = $pdo->query('SELECT * FROM students ORDER BY FullName')->fetchAll();
} elseif ($reportType === 'rooms') {
    $rooms = $pdo->query('SELECT * FROM rooms ORDER BY Block, RoomNumber')->fetchAll();
} elseif ($reportType === 'allocations') {
    $allocations = $pdo->query(
        "SELECT a.*, s.FullName, s.RegistrationNo, r.RoomNumber, r.Block
         FROM allocations a
         JOIN students s ON s.StudentID = a.StudentID
         JOIN rooms r ON r.RoomID = a.RoomID
         ORDER BY a.AllocationID DESC"
    )->fetchAll();
} elseif ($reportType === 'payments') {
    $payments = $pdo->query(
        "SELECT p.*, s.FullName, s.RegistrationNo, r.RoomNumber, r.Block
         FROM payments p
         JOIN allocations a ON a.AllocationID = p.AllocationID
         JOIN students s ON s.StudentID = a.StudentID
         JOIN rooms r ON r.RoomID = a.RoomID
         ORDER BY p.PaymentMonth DESC, p.PaymentID DESC"
    )->fetchAll();
    $paymentSummary = $pdo->query(
        "SELECT PaymentStatus, COUNT(*) as cnt, COALESCE(SUM(Amount),0) as total
         FROM payments GROUP BY PaymentStatus"
    )->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold mb-0">Reports</h3>
    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print</button>
  </div>

  <ul class="nav nav-pills mb-4">
    <li class="nav-item"><a class="nav-link <?= $reportType === 'students' ? 'active' : '' ?>" href="reports.php?type=students">Student Report</a></li>
    <li class="nav-item"><a class="nav-link <?= $reportType === 'rooms' ? 'active' : '' ?>" href="reports.php?type=rooms">Room Report</a></li>
    <li class="nav-item"><a class="nav-link <?= $reportType === 'allocations' ? 'active' : '' ?>" href="reports.php?type=allocations">Allocation Report</a></li>
    <li class="nav-item"><a class="nav-link <?= $reportType === 'payments' ? 'active' : '' ?>" href="reports.php?type=payments">Payment Report</a></li>
  </ul>

  <?php if ($reportType === 'students'): ?>
  <div class="card">
    <div class="card-header">Student Report — <?= count($students) ?> total students</div>
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>Reg No</th><th>Name</th><th>Gender</th><th>Phone</th><th>Email</th><th>Faculty</th><th>Department</th><th>Year</th></tr></thead>
        <tbody>
        <?php foreach ($students as $s): ?>
          <tr>
            <td><?= clean($s['RegistrationNo']) ?></td><td><?= clean($s['FullName']) ?></td><td><?= clean($s['Gender']) ?></td>
            <td><?= clean($s['Phone']) ?></td><td><?= clean($s['Email']) ?></td><td><?= clean($s['Faculty']) ?></td>
            <td><?= clean($s['Department']) ?></td><td><?= clean($s['AcademicYear']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($reportType === 'rooms'): ?>
  <div class="card">
    <div class="card-header">Room Report — <?= count($rooms) ?> total rooms</div>
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>Room No</th><th>Block</th><th>Floor</th><th>Capacity</th><th>Occupied</th><th>Vacant</th><th>Fee/Month</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rooms as $r): ?>
          <tr>
            <td><?= clean($r['RoomNumber']) ?></td><td><?= clean($r['Block']) ?></td><td><?= (int)$r['Floor'] ?></td>
            <td><?= (int)$r['Capacity'] ?></td><td><?= (int)$r['OccupiedBeds'] ?></td>
            <td><?= (int)($r['Capacity'] - $r['OccupiedBeds']) ?></td>
            <td><?= formatMoney($r['MonthlyFee']) ?></td><td><?= statusBadge($r['Status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($reportType === 'allocations'): ?>
  <div class="card">
    <div class="card-header">Allocation Report — <?= count($allocations) ?> total records</div>
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>Student</th><th>Reg No</th><th>Room</th><th>Check-In</th><th>Expected Checkout</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($allocations as $a): ?>
          <tr>
            <td><?= clean($a['FullName']) ?></td><td><?= clean($a['RegistrationNo']) ?></td>
            <td><?= clean($a['Block'] . '-' . $a['RoomNumber']) ?></td>
            <td><?= formatDate($a['CheckInDate']) ?></td><td><?= formatDate($a['ExpectedCheckout']) ?></td>
            <td><?= statusBadge($a['AllocationStatus']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($reportType === 'payments'): ?>
  <div class="row g-3 mb-3">
    <?php foreach ($paymentSummary as $ps): ?>
    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small text-uppercase"><?= clean($ps['PaymentStatus']) ?></div>
        <h4 class="fw-bold mb-0"><?= (int)$ps['cnt'] ?> payments</h4>
        <div class="text-muted"><?= formatMoney($ps['total']) ?></div>
      </div></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="card-header">Payment Report — <?= count($payments) ?> total records</div>
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>Student</th><th>Room</th><th>Month</th><th>Amount</th><th>Payment Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= clean($p['FullName']) ?></td><td><?= clean($p['Block'] . '-' . $p['RoomNumber']) ?></td>
            <td><?= clean($p['PaymentMonth']) ?></td><td><?= formatMoney($p['Amount']) ?></td>
            <td><?= formatDate($p['PaymentDate']) ?></td><td><?= statusBadge($p['PaymentStatus']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
