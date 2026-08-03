<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Rooms';

// ============================================================
// HANDLE FORM SUBMISSIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid session token. Please try again.');
        redirect('rooms.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['RoomID'] ?? 0);
        try {
            $stmt = $pdo->prepare('DELETE FROM rooms WHERE RoomID = ?');
            $stmt->execute([$id]);
            setFlash('success', 'Room deleted successfully.');
        } catch (PDOException $e) {
            setFlash('danger', 'Cannot delete: this room has existing allocation records.');
        }
        redirect('rooms.php');
    }

    if ($action === 'create' || $action === 'update') {
        $roomId       = (int)($_POST['RoomID'] ?? 0);
        $roomNumber   = clean($_POST['RoomNumber'] ?? '');
        $block        = clean($_POST['Block'] ?? '');
        $floor        = (int)($_POST['Floor'] ?? 0);
        $capacity     = (int)($_POST['Capacity'] ?? 0);
        $monthlyFee   = (float)($_POST['MonthlyFee'] ?? 0);
        $status       = clean($_POST['Status'] ?? '');

        $errors = [];
        if ($roomNumber === '') $errors[] = 'Room number is required.';
        if ($block === '') $errors[] = 'Block is required.';
        if ($capacity <= 0) $errors[] = 'Capacity must be greater than zero.';
        if ($monthlyFee <= 0) $errors[] = 'Monthly fee must be greater than zero.';
        if (!in_array($status, ['Available', 'Full', 'Maintenance'], true)) $errors[] = 'Please select a valid status.';

        // Determine current occupied beds (needed so capacity isn't set below occupancy)
        $occupiedBeds = 0;
        if ($action === 'update') {
            $curStmt = $pdo->prepare('SELECT OccupiedBeds FROM rooms WHERE RoomID = ?');
            $curStmt->execute([$roomId]);
            $occupiedBeds = (int)$curStmt->fetchColumn();
            if (!$errors && $capacity < $occupiedBeds) {
                $errors[] = "Capacity cannot be less than currently occupied beds ($occupiedBeds).";
            }
        }

        if (!$errors) {
            $checkStmt = $pdo->prepare('SELECT RoomID FROM rooms WHERE RoomNumber = ? AND RoomID != ?');
            $checkStmt->execute([$roomNumber, $roomId]);
            if ($checkStmt->fetch()) {
                $errors[] = 'This room number is already in use.';
            }
        }

        if ($errors) {
            setFlash('danger', implode(' ', $errors));
            redirect('rooms.php');
        }

        // Auto-correct status based on occupancy vs capacity, unless Maintenance chosen
        if ($status !== 'Maintenance') {
            $status = ($occupiedBeds >= $capacity && $capacity > 0) ? 'Full' : 'Available';
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO rooms (RoomNumber, Block, Floor, Capacity, OccupiedBeds, MonthlyFee, Status)
                 VALUES (?, ?, ?, ?, 0, ?, ?)'
            );
            $stmt->execute([$roomNumber, $block, $floor, $capacity, $monthlyFee, $status]);
            setFlash('success', 'Room added successfully.');
        } else {
            $stmt = $pdo->prepare(
                'UPDATE rooms SET RoomNumber=?, Block=?, Floor=?, Capacity=?, MonthlyFee=?, Status=? WHERE RoomID=?'
            );
            $stmt->execute([$roomNumber, $block, $floor, $capacity, $monthlyFee, $status, $roomId]);
            setFlash('success', 'Room updated successfully.');
        }
        redirect('rooms.php');
    }
}

// ============================================================
// SEARCH
// ============================================================
$search = clean($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM rooms WHERE RoomNumber LIKE ? OR Block LIKE ? OR Status LIKE ? ORDER BY RoomID DESC"
    );
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query('SELECT * FROM rooms ORDER BY RoomID DESC');
}
$rooms = $stmt->fetchAll();

$token = csrfToken();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold mb-0">Rooms</h3>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#createRoomModal">
      <i class="fa-solid fa-plus me-1"></i> Add Room
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
          <input type="text" name="q" class="form-control" placeholder="Search by room number, block, or status..." value="<?= clean($search) ?>">
        </div>
        <div class="col-md-2"><button class="btn btn-brand w-100" type="submit"><i class="fa-solid fa-search me-1"></i>Search</button></div>
        <div class="col-md-2"><a href="rooms.php" class="btn btn-outline-secondary w-100">Reset</a></div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>ID</th><th>Room No</th><th>Block</th><th>Floor</th><th>Capacity</th><th>Occupied</th><th>Fee/Month</th><th>Status</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (!$rooms): ?><tr><td colspan="9" class="text-center text-muted py-4">No rooms found</td></tr><?php endif; ?>
          <?php foreach ($rooms as $r): ?>
          <tr>
            <td>#<?= (int)$r['RoomID'] ?></td>
            <td><?= clean($r['RoomNumber']) ?></td>
            <td><?= clean($r['Block']) ?></td>
            <td><?= (int)$r['Floor'] ?></td>
            <td><?= (int)$r['Capacity'] ?></td>
            <td><?= (int)$r['OccupiedBeds'] ?></td>
            <td><?= formatMoney($r['MonthlyFee']) ?></td>
            <td><?= statusBadge($r['Status']) ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary" title="Edit"
                onclick='openEditModal("editRoomModal", <?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                <i class="fa-solid fa-pen"></i></button>
              <button class="btn btn-sm btn-outline-danger" title="Delete"
                data-bs-toggle="modal" data-bs-target="#deleteRoomModal"
                onclick="document.getElementById('deleteRoomId').value='<?= (int)$r['RoomID'] ?>'; document.getElementById('deleteRoomName').textContent='<?= clean($r['RoomNumber']) ?>';">
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
<div class="modal fade" id="createRoomModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="create">
      <div class="modal-header"><h5 class="modal-title">Add Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><?php include __DIR__ . '/includes/room_form_fields.php'; ?></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Save Room</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="RoomID">
      <div class="modal-header"><h5 class="modal-title">Edit Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><?php include __DIR__ . '/includes/room_form_fields.php'; ?></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Update Room</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content confirm-delete-form" data-confirm-message="Delete this room permanently?">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="RoomID" id="deleteRoomId">
      <div class="modal-header"><h5 class="modal-title">Confirm Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">Are you sure you want to delete room <strong id="deleteRoomName"></strong>?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
