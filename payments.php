<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Payments';

// ============================================================
// HANDLE FORM SUBMISSIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid session token. Please try again.');
        redirect('payments.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['PaymentID'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM payments WHERE PaymentID = ?');
        $stmt->execute([$id]);
        setFlash('success', 'Payment record deleted successfully.');
        redirect('payments.php');
    }

    if ($action === 'create' || $action === 'update') {
        $paymentId    = (int)($_POST['PaymentID'] ?? 0);
        $allocationId = (int)($_POST['AllocationID'] ?? 0);
        $paymentMonth = clean($_POST['PaymentMonth'] ?? '');
        $amount       = (float)($_POST['Amount'] ?? 0);
        $paymentDate  = clean($_POST['PaymentDate'] ?? '');
        $status       = clean($_POST['PaymentStatus'] ?? '');

        $errors = [];
        if (!$allocationId) $errors[] = 'Please select an allocation (student/room).';
        if (!preg_match('/^\d{4}-\d{2}$/', $paymentMonth)) $errors[] = 'Please select a valid payment month.';
        if ($amount <= 0) $errors[] = 'Amount must be greater than zero.';
        if (!in_array($status, ['Paid', 'Pending', 'Overdue'], true)) $errors[] = 'Please select a valid payment status.';
        if ($status === 'Paid' && !$paymentDate) $errors[] = 'Payment date is required when status is Paid.';

        if ($errors) {
            setFlash('danger', implode(' ', $errors));
            redirect('payments.php');
        }

        $paymentDateValue = $paymentDate !== '' ? $paymentDate : null;

        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO payments (AllocationID, PaymentMonth, Amount, PaymentDate, PaymentStatus)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$allocationId, $paymentMonth, $amount, $paymentDateValue, $status]);
            setFlash('success', 'Payment recorded successfully.');
        } else {
            $stmt = $pdo->prepare(
                'UPDATE payments SET AllocationID=?, PaymentMonth=?, Amount=?, PaymentDate=?, PaymentStatus=? WHERE PaymentID=?'
            );
            $stmt->execute([$allocationId, $paymentMonth, $amount, $paymentDateValue, $status, $paymentId]);
            setFlash('success', 'Payment updated successfully.');
        }
        redirect('payments.php');
    }
}

// ============================================================
// SEARCH
// ============================================================
$search = clean($_GET['q'] ?? '');
$baseQuery = "SELECT p.*, s.FullName, s.RegistrationNo, r.RoomNumber, r.Block
              FROM payments p
              JOIN allocations a ON a.AllocationID = p.AllocationID
              JOIN students s ON s.StudentID = a.StudentID
              JOIN rooms r ON r.RoomID = a.RoomID";
if ($search !== '') {
    $stmt = $pdo->prepare($baseQuery . " WHERE s.FullName LIKE ? OR p.PaymentMonth LIKE ? OR p.PaymentStatus LIKE ?
                           ORDER BY p.PaymentID DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query($baseQuery . ' ORDER BY p.PaymentID DESC');
}
$payments = $stmt->fetchAll();

// Dropdown data: all allocations (active preferred) with student/room labels
$allocationOptions = $pdo->query(
    "SELECT a.AllocationID, s.FullName, s.RegistrationNo, r.RoomNumber, r.Block, a.AllocationStatus
     FROM allocations a
     JOIN students s ON s.StudentID = a.StudentID
     JOIN rooms r ON r.RoomID = a.RoomID
     ORDER BY a.AllocationStatus = 'Active' DESC, s.FullName"
)->fetchAll();

$token = csrfToken();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold mb-0">Payments</h3>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#createPaymentModal">
      <i class="fa-solid fa-plus me-1"></i> Add Payment
    </button>
  </div>

  <?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= clean($flash['type']) ?> alert-dismissible fade show alert-auto-dismiss" role="alert">
      <?= clean($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-2">
        <div class="col-md-8">
          <input type="text" name="q" class="form-control" placeholder="Search by student name, payment month, or status..." value="<?= clean($search) ?>">
        </div>
        <div class="col-md-2"><button class="btn btn-brand w-100" type="submit"><i class="fa-solid fa-search me-1"></i>Search</button></div>
        <div class="col-md-2"><a href="payments.php" class="btn btn-outline-secondary w-100">Reset</a></div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="fa-solid fa-receipt me-2"></i>Payment History</div>
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>ID</th><th>Student</th><th>Room</th><th>Month</th><th>Amount</th><th>Payment Date</th><th>Status</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (!$payments): ?><tr><td colspan="8" class="text-center text-muted py-4">No payment records found</td></tr><?php endif; ?>
          <?php foreach ($payments as $p): ?>
          <tr>
            <td>#<?= (int)$p['PaymentID'] ?></td>
            <td><?= clean($p['FullName']) ?></td>
            <td><?= clean($p['Block'] . '-' . $p['RoomNumber']) ?></td>
            <td><?= clean($p['PaymentMonth']) ?></td>
            <td><?= formatMoney($p['Amount']) ?></td>
            <td><?= formatDate($p['PaymentDate']) ?></td>
            <td><?= statusBadge($p['PaymentStatus']) ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary" title="Edit"
                onclick='openEditModal("editPaymentModal", <?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                <i class="fa-solid fa-pen"></i></button>
              <button class="btn btn-sm btn-outline-danger" title="Delete"
                data-bs-toggle="modal" data-bs-target="#deletePaymentModal"
                onclick="document.getElementById('deletePaymentId').value='<?= (int)$p['PaymentID'] ?>';">
                <i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</main>

<!-- CREATE MODAL -->
<div class="modal fade" id="createPaymentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="create">
      <div class="modal-header"><h5 class="modal-title">Add Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><?php include __DIR__ . '/includes/payment_form_fields.php'; ?></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Save Payment</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editPaymentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="PaymentID">
      <div class="modal-header"><h5 class="modal-title">Edit Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><?php include __DIR__ . '/includes/payment_form_fields.php'; ?></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Update Payment</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content confirm-delete-form" data-confirm-message="Delete this payment record?">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="PaymentID" id="deletePaymentId">
      <div class="modal-header"><h5 class="modal-title">Confirm Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">Are you sure you want to delete this payment record?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
