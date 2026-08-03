<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Allocations';

// ============================================================
// HANDLE FORM SUBMISSIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid session token. Please try again.');
        redirect('allocations.php');
    }

    $action = $_POST['action'] ?? '';

    // ---------- ASSIGN (create new allocation) ----------
    if ($action === 'assign') {
        $studentId  = (int)($_POST['StudentID'] ?? 0);
        $roomId     = (int)($_POST['RoomID'] ?? 0);
        $checkIn    = $_POST['CheckInDate'] ?? '';
        $checkout   = $_POST['ExpectedCheckout'] ?? '';

        $errors = [];
        if (!$studentId) $errors[] = 'Please select a student.';
        if (!$roomId) $errors[] = 'Please select a room.';
        if (!$checkIn) $errors[] = 'Check-in date is required.';
        if (!$checkout) $errors[] = 'Expected checkout date is required.';
        if ($checkIn && $checkout && strtotime($checkout) <= strtotime($checkIn)) {
            $errors[] = 'Expected checkout must be after check-in date.';
        }

        if (!$errors) {
            // Ensure the student doesn't already have an active allocation
            $activeCheck = $pdo->prepare("SELECT AllocationID FROM allocations WHERE StudentID = ? AND AllocationStatus = 'Active'");
            $activeCheck->execute([$studentId]);
            if ($activeCheck->fetch()) {
                $errors[] = 'This student already has an active room allocation.';
            }
        }

        if (!$errors) {
            $roomStmt = $pdo->prepare('SELECT Capacity, OccupiedBeds, Status FROM rooms WHERE RoomID = ? FOR UPDATE');
            $pdo->beginTransaction();
            $roomStmt->execute([$roomId]);
            $room = $roomStmt->fetch();
            if (!$room) {
                $errors[] = 'Selected room does not exist.';
            } elseif ($room['Status'] === 'Maintenance') {
                $errors[] = 'This room is under maintenance and cannot be assigned.';
            } elseif ($room['OccupiedBeds'] >= $room['Capacity']) {
                $errors[] = 'This room is already full.';
            }

            if ($errors) {
                $pdo->rollBack();
            } else {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO allocations (StudentID, RoomID, CheckInDate, ExpectedCheckout, AllocationStatus)
                     VALUES (?, ?, ?, ?, "Active")'
                );
                $insertStmt->execute([$studentId, $roomId, $checkIn, $checkout]);

                $newOccupied = $room['OccupiedBeds'] + 1;
                $newStatus = ($newOccupied >= $room['Capacity']) ? 'Full' : 'Available';
                $updateRoom = $pdo->prepare('UPDATE rooms SET OccupiedBeds = ?, Status = ? WHERE RoomID = ?');
                $updateRoom->execute([$newOccupied, $newStatus, $roomId]);

                $pdo->commit();
                setFlash('success', 'Student allocated to room successfully.');
                redirect('allocations.php');
            }
        }

        if ($errors) {
            setFlash('danger', implode(' ', $errors));
            redirect('allocations.php');
        }
    }

    // ---------- CHECKOUT ----------
    if ($action === 'checkout') {
        $allocationId = (int)($_POST['AllocationID'] ?? 0);

        $pdo->beginTransaction();
        $allocStmt = $pdo->prepare('SELECT * FROM allocations WHERE AllocationID = ? FOR UPDATE');
        $allocStmt->execute([$allocationId]);
        $alloc = $allocStmt->fetch();

        if (!$alloc || $alloc['AllocationStatus'] !== 'Active') {
            $pdo->rollBack();
            setFlash('danger', 'This allocation is not active or does not exist.');
            redirect('allocations.php');
        }

        $pdo->prepare("UPDATE allocations SET AllocationStatus = 'Checked Out' WHERE AllocationID = ?")->execute([$allocationId]);

        $roomStmt = $pdo->prepare('SELECT Capacity, OccupiedBeds FROM rooms WHERE RoomID = ? FOR UPDATE');
        $roomStmt->execute([$alloc['RoomID']]);
        $room = $roomStmt->fetch();
        $newOccupied = max(0, $room['OccupiedBeds'] - 1);
        $newStatus = ($newOccupied >= $room['Capacity']) ? 'Full' : 'Available';
        $pdo->prepare('UPDATE rooms SET OccupiedBeds = ?, Status = ? WHERE RoomID = ?')
            ->execute([$newOccupied, $newStatus, $alloc['RoomID']]);

        $pdo->commit();
        setFlash('success', 'Student checked out successfully.');
        redirect('allocations.php');
    }

    // ---------- TRANSFER ----------
    if ($action === 'transfer') {
        $allocationId = (int)($_POST['AllocationID'] ?? 0);
        $newRoomId    = (int)($_POST['NewRoomID'] ?? 0);

        $errors = [];
        if (!$newRoomId) $errors[] = 'Please select a destination room.';

        $pdo->beginTransaction();
        $allocStmt = $pdo->prepare('SELECT * FROM allocations WHERE AllocationID = ? FOR UPDATE');
        $allocStmt->execute([$allocationId]);
        $alloc = $allocStmt->fetch();

        if (!$alloc || $alloc['AllocationStatus'] !== 'Active') {
            $errors[] = 'This allocation is not active or does not exist.';
        } elseif ($newRoomId === (int)$alloc['RoomID']) {
            $errors[] = 'Please choose a different room to transfer into.';
        }

        if (!$errors) {
            $newRoomStmt = $pdo->prepare('SELECT Capacity, OccupiedBeds, Status FROM rooms WHERE RoomID = ? FOR UPDATE');
            $newRoomStmt->execute([$newRoomId]);
            $newRoom = $newRoomStmt->fetch();
            if (!$newRoom) {
                $errors[] = 'Destination room does not exist.';
            } elseif ($newRoom['Status'] === 'Maintenance') {
                $errors[] = 'Destination room is under maintenance.';
            } elseif ($newRoom['OccupiedBeds'] >= $newRoom['Capacity']) {
                $errors[] = 'Destination room is full.';
            }
        }

        if ($errors) {
            $pdo->rollBack();
            setFlash('danger', implode(' ', $errors));
            redirect('allocations.php');
        }

        // Close old allocation
        $pdo->prepare("UPDATE allocations SET AllocationStatus = 'Transferred' WHERE AllocationID = ?")->execute([$allocationId]);

        // Free up old room
        $oldRoomStmt = $pdo->prepare('SELECT Capacity, OccupiedBeds FROM rooms WHERE RoomID = ? FOR UPDATE');
        $oldRoomStmt->execute([$alloc['RoomID']]);
        $oldRoom = $oldRoomStmt->fetch();
        $oldOccupied = max(0, $oldRoom['OccupiedBeds'] - 1);
        $oldStatus = ($oldOccupied >= $oldRoom['Capacity']) ? 'Full' : 'Available';
        $pdo->prepare('UPDATE rooms SET OccupiedBeds = ?, Status = ? WHERE RoomID = ?')
            ->execute([$oldOccupied, $oldStatus, $alloc['RoomID']]);

        // Create new allocation in destination room
        $pdo->prepare(
            'INSERT INTO allocations (StudentID, RoomID, CheckInDate, ExpectedCheckout, AllocationStatus)
             VALUES (?, ?, CURDATE(), ?, "Active")'
        )->execute([$alloc['StudentID'], $newRoomId, $alloc['ExpectedCheckout']]);

        // Occupy new room
        $newRoomStmt2 = $pdo->prepare('SELECT Capacity, OccupiedBeds FROM rooms WHERE RoomID = ? FOR UPDATE');
        $newRoomStmt2->execute([$newRoomId]);
        $newRoom2 = $newRoomStmt2->fetch();
        $newOccupied2 = $newRoom2['OccupiedBeds'] + 1;
        $newStatus2 = ($newOccupied2 >= $newRoom2['Capacity']) ? 'Full' : 'Available';
        $pdo->prepare('UPDATE rooms SET OccupiedBeds = ?, Status = ? WHERE RoomID = ?')
            ->execute([$newOccupied2, $newStatus2, $newRoomId]);

        $pdo->commit();
        setFlash('success', 'Student transferred to new room successfully.');
        redirect('allocations.php');
    }
}

// ============================================================
// SEARCH / LIST (Allocation History)
// ============================================================
$search = clean($_GET['q'] ?? '');
$baseQuery = "SELECT a.*, s.FullName, s.RegistrationNo, r.RoomNumber, r.Block
              FROM allocations a
              JOIN students s ON s.StudentID = a.StudentID
              JOIN rooms r ON r.RoomID = a.RoomID";
if ($search !== '') {
    $stmt = $pdo->prepare($baseQuery . " WHERE s.FullName LIKE ? OR r.RoomNumber LIKE ? OR a.AllocationStatus LIKE ?
                           ORDER BY a.AllocationID DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query($baseQuery . ' ORDER BY a.AllocationID DESC');
}
$allocations = $stmt->fetchAll();

// Dropdown data: students without an active allocation
$availableStudents = $pdo->query(
    "SELECT StudentID, FullName, RegistrationNo FROM students
     WHERE StudentID NOT IN (SELECT StudentID FROM allocations WHERE AllocationStatus = 'Active')
     ORDER BY FullName"
)->fetchAll();

// Dropdown data: rooms with free capacity
$availableRoomsForAssign = $pdo->query(
    "SELECT RoomID, RoomNumber, Block, Capacity, OccupiedBeds FROM rooms
     WHERE Status != 'Maintenance' AND OccupiedBeds < Capacity ORDER BY RoomNumber"
)->fetchAll();

$token = csrfToken();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold mb-0">Allocations</h3>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#assignModal">
      <i class="fa-solid fa-plus me-1"></i> Assign Student to Room
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
          <input type="text" name="q" class="form-control" placeholder="Search by student name, room number, or status..." value="<?= clean($search) ?>">
        </div>
        <div class="col-md-2"><button class="btn btn-brand w-100" type="submit"><i class="fa-solid fa-search me-1"></i>Search</button></div>
        <div class="col-md-2"><a href="allocations.php" class="btn btn-outline-secondary w-100">Reset</a></div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="fa-solid fa-clock-rotate-left me-2"></i>Allocation History</div>
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>ID</th><th>Student</th><th>Reg No</th><th>Room</th><th>Check-In</th><th>Expected Checkout</th><th>Status</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (!$allocations): ?><tr><td colspan="8" class="text-center text-muted py-4">No allocation records found</td></tr><?php endif; ?>
          <?php foreach ($allocations as $a): ?>
          <tr>
            <td>#<?= (int)$a['AllocationID'] ?></td>
            <td><?= clean($a['FullName']) ?></td>
            <td><?= clean($a['RegistrationNo']) ?></td>
            <td><?= clean($a['Block'] . '-' . $a['RoomNumber']) ?></td>
            <td><?= formatDate($a['CheckInDate']) ?></td>
            <td><?= formatDate($a['ExpectedCheckout']) ?></td>
            <td><?= statusBadge($a['AllocationStatus']) ?></td>
            <td class="text-end">
              <?php if ($a['AllocationStatus'] === 'Active'): ?>
                <button class="btn btn-sm btn-outline-info" title="Transfer"
                  data-bs-toggle="modal" data-bs-target="#transferModal"
                  onclick="document.getElementById('transferAllocationId').value='<?= (int)$a['AllocationID'] ?>'; document.getElementById('transferStudentName').textContent='<?= clean($a['FullName']) ?>';">
                  <i class="fa-solid fa-right-left"></i></button>
                <button class="btn btn-sm btn-outline-warning" title="Checkout"
                  data-bs-toggle="modal" data-bs-target="#checkoutModal"
                  onclick="document.getElementById('checkoutAllocationId').value='<?= (int)$a['AllocationID'] ?>'; document.getElementById('checkoutStudentName').textContent='<?= clean($a['FullName']) ?>';">
                  <i class="fa-solid fa-door-closed"></i></button>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</main>

<!-- ASSIGN MODAL -->
<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="assign">
      <div class="modal-header"><h5 class="modal-title">Assign Student to Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Student *</label>
            <select name="StudentID" class="form-select" required>
              <option value="">Select student</option>
              <?php foreach ($availableStudents as $s): ?>
                <option value="<?= (int)$s['StudentID'] ?>"><?= clean($s['FullName']) ?> (<?= clean($s['RegistrationNo']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <?php if (!$availableStudents): ?><div class="form-text text-warning">All students already have an active allocation.</div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Room *</label>
            <select name="RoomID" class="form-select" required>
              <option value="">Select room</option>
              <?php foreach ($availableRoomsForAssign as $r): ?>
                <option value="<?= (int)$r['RoomID'] ?>"><?= clean($r['Block'] . '-' . $r['RoomNumber']) ?> (<?= (int)$r['OccupiedBeds'] ?>/<?= (int)$r['Capacity'] ?> beds)</option>
              <?php endforeach; ?>
            </select>
            <?php if (!$availableRoomsForAssign): ?><div class="form-text text-warning">No rooms with free capacity.</div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Check-In Date *</label>
            <input type="date" name="CheckInDate" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Expected Checkout *</label>
            <input type="date" name="ExpectedCheckout" class="form-control" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Assign</button>
      </div>
    </form>
  </div>
</div>

<!-- TRANSFER MODAL -->
<div class="modal fade" id="transferModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="transfer">
      <input type="hidden" name="AllocationID" id="transferAllocationId">
      <div class="modal-header"><h5 class="modal-title">Transfer Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p>Transfer <strong id="transferStudentName"></strong> to a new room:</p>
        <label class="form-label">New Room *</label>
        <select name="NewRoomID" class="form-select" required>
          <option value="">Select room</option>
          <?php foreach ($availableRoomsForAssign as $r): ?>
            <option value="<?= (int)$r['RoomID'] ?>"><?= clean($r['Block'] . '-' . $r['RoomNumber']) ?> (<?= (int)$r['OccupiedBeds'] ?>/<?= (int)$r['Capacity'] ?> beds)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Transfer</button>
      </div>
    </form>
  </div>
</div>

<!-- CHECKOUT MODAL -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content confirm-delete-form" data-confirm-message="Check out this student?">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="checkout">
      <input type="hidden" name="AllocationID" id="checkoutAllocationId">
      <div class="modal-header"><h5 class="modal-title">Confirm Checkout</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">Check out <strong id="checkoutStudentName"></strong> from their current room?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning">Checkout</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
