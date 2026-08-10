<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Students';

/** Get the short abbreviation or display name for a department */
function getDeptShortName($deptName) {
    $decoded = htmlspecialchars_decode($deptName ?? '', ENT_QUOTES);
    if (preg_match('/\(([^)]+)\)/', $decoded, $matches)) {
        return $matches[1];
    }
    if (str_starts_with($decoded, 'Department of ')) {
        return substr($decoded, 14);
    }
    return $decoded;
}

/** Properly decode and re-encode to prevent double HTML encoding issues and display correctly */
function displayClean($value) {
    return htmlspecialchars(htmlspecialchars_decode($value ?? '', ENT_QUOTES), ENT_QUOTES, 'UTF-8');
}


// ============================================================
// HANDLE FORM SUBMISSIONS (Create / Update / Delete)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid session token. Please try again.');
        redirect('students.php');
    }

    $action = $_POST['action'] ?? '';

    // ---------- DELETE ----------
    if ($action === 'delete') {
        $id = (int)($_POST['StudentID'] ?? 0);
        try {
            $stmt = $pdo->prepare('DELETE FROM students WHERE StudentID = ?');
            $stmt->execute([$id]);
            setFlash('success', 'Student deleted successfully.');
        } catch (PDOException $e) {
            setFlash('danger', 'Cannot delete: this student has existing room allocation records.');
        }
        redirect('students.php');
    }

    // ---------- CREATE / UPDATE ----------
    if ($action === 'create' || $action === 'update') {
        $studentId      = (int)($_POST['StudentID'] ?? 0);
        $registrationNo = clean($_POST['RegistrationNo'] ?? '');
        $fullName       = clean($_POST['FullName'] ?? '');
        $gender         = clean($_POST['Gender'] ?? '');
        $phone          = clean($_POST['Phone'] ?? '');
        $email          = clean($_POST['Email'] ?? '');
        $faculty        = clean($_POST['Faculty'] ?? '');
        $department     = clean($_POST['Department'] ?? '');
        $academicYear   = clean($_POST['AcademicYear'] ?? '');

        $errors = [];
        if ($registrationNo === '') $errors[] = 'Registration number is required.';
        if ($fullName === '') $errors[] = 'Full name is required.';
        if (!in_array($gender, ['Male', 'Female', 'Other'], true)) $errors[] = 'Please select a valid gender.';
        if (!isValidPhone($phone)) $errors[] = 'Phone number must contain 9-15 digits only.';
        if (!isValidEmail($email)) $errors[] = 'Please enter a valid email address.';
        if ($faculty === '') $errors[] = 'Faculty is required.';
        if ($department === '') $errors[] = 'Department is required.';
        if ($academicYear === '') $errors[] = 'Academic year is required.';

        // Uniqueness check for RegistrationNo
        if (!$errors) {
            $checkStmt = $pdo->prepare('SELECT StudentID FROM students WHERE RegistrationNo = ? AND StudentID != ?');
            $checkStmt->execute([$registrationNo, $studentId]);
            if ($checkStmt->fetch()) {
                $errors[] = 'This registration number is already in use.';
            }
        }

        if ($errors) {
            setFlash('danger', implode(' ', $errors));
            redirect('students.php');
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO students (RegistrationNo, FullName, Gender, Phone, Email, Faculty, Department, AcademicYear)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$registrationNo, $fullName, $gender, $phone, $email, $faculty, $department, $academicYear]);
            setFlash('success', 'Student added successfully.');
        } else {
            $stmt = $pdo->prepare(
                'UPDATE students SET RegistrationNo=?, FullName=?, Gender=?, Phone=?, Email=?, Faculty=?, Department=?, AcademicYear=?
                 WHERE StudentID=?'
            );
            $stmt->execute([$registrationNo, $fullName, $gender, $phone, $email, $faculty, $department, $academicYear, $studentId]);
            setFlash('success', 'Student updated successfully.');
        }
        redirect('students.php');
    }
}

// ============================================================
// SEARCH
// ============================================================
$search = clean($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM students
         WHERE FullName LIKE ? OR RegistrationNo LIKE ? OR StudentID = ?
         ORDER BY StudentID DESC"
    );
    $like = "%$search%";
    $stmt->execute([$like, $like, is_numeric($search) ? (int)$search : -1]);
} else {
    $stmt = $pdo->query('SELECT * FROM students ORDER BY StudentID DESC');
}
$students = $stmt->fetchAll();

$token = csrfToken();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
  <div class="mb-4">
    <h3 class="fw-bold mb-0">Students</h3>
  </div>

  <?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= clean($flash['type']) ?> alert-dismissible fade show alert-auto-dismiss" role="alert">
      <?= clean($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        <div class="flex-grow-1" style="min-width: 250px;">
          <input type="text" name="q" class="form-control" placeholder="Search by name, registration no, or student ID..." value="<?= clean($search) ?>">
        </div>
        <div class="d-flex gap-2 align-items-center">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search me-1"></i>Search</button>
          <a href="students.php" class="btn btn-outline-secondary">Reset</a>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStudentModal">
            <i class="fa-solid fa-plus me-1"></i> Add Student
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table table-hover mb-0" id="studentsTable" style="table-layout: fixed; width: 100%;">
        <colgroup>
          <col style="width: 5%;">
          <col style="width: 10%;">
          <col style="width: 15%;">
          <col style="width: 7%;">
          <col style="width: 11%;">
          <col style="width: 16%;">
          <col style="width: 12%;">
          <col style="width: 9%;">
          <col style="width: 7%;">
          <col style="width: 8%;">
        </colgroup>
        <thead class="thead-darker">
          <tr>
            <th>ID</th><th>Reg. No</th><th>Full Name</th><th>Gender</th><th>Phone</th>
            <th>Email</th><th>Faculty</th><th>Department</th><th>Year</th><th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$students): ?>
          <tr><td colspan="10" class="text-center text-muted py-4">No students found</td></tr>
          <?php endif; ?>
          <?php foreach ($students as $s): ?>
          <?php
          $yearMap = [
              '1st Year' => '1st Yr',
              '2nd Year' => '2nd Yr',
              '3rd Year' => '3rd Yr',
              '4th Year' => '4th Yr'
          ];
          $displayYear = $yearMap[$s['AcademicYear']] ?? $s['AcademicYear'];
          ?>
          <tr>
            <td>#<?= (int)$s['StudentID'] ?></td>
            <td title="<?= displayClean($s['RegistrationNo']) ?>"><?= displayClean($s['RegistrationNo']) ?></td>
            <td title="<?= displayClean($s['FullName']) ?>"><?= displayClean($s['FullName']) ?></td>
            <td title="<?= displayClean($s['Gender']) ?>"><?= displayClean($s['Gender']) ?></td>
            <td title="<?= displayClean($s['Phone']) ?>"><?= displayClean($s['Phone']) ?></td>
            <td title="<?= displayClean($s['Email']) ?>"><?= displayClean($s['Email']) ?></td>
            <td title="<?= displayClean($s['Faculty']) ?>"><?= displayClean($s['Faculty']) ?></td>
            <td title="<?= displayClean($s['Department']) ?>"><?= displayClean(getDeptShortName($s['Department'])) ?></td>
            <td><span class="badge badge-year" title="<?= displayClean($s['AcademicYear']) ?>"><?= displayClean($displayYear) ?></span></td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center align-items-center">
                <button class="btn btn-sm btn-outline-primary"
                  onclick='openEditModal("editStudentModal", <?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                  title="Edit"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger" title="Delete"
                  data-bs-toggle="modal" data-bs-target="#deleteStudentModal"
                  onclick="document.getElementById('deleteStudentId').value='<?= (int)$s['StudentID'] ?>'; document.getElementById('deleteStudentName').textContent='<?= clean($s['FullName']) ?>';">
                  <i class="fa-solid fa-trash"></i></button>
              </div>
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
<div class="modal fade" id="createStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="create">
      <div class="modal-header"><h5 class="modal-title">Add Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <?php include __DIR__ . '/includes/student_form_fields.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Save Student</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="StudentID">
      <div class="modal-header"><h5 class="modal-title">Edit Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <?php include __DIR__ . '/includes/student_form_fields.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Update Student</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content confirm-delete-form" data-confirm-message="Delete this student permanently?">
      <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="StudentID" id="deleteStudentId">
      <div class="modal-header"><h5 class="modal-title">Confirm Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        Are you sure you want to delete <strong id="deleteStudentName"></strong>? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const facultyData = {
    "Faculty of Technology": [
      "Department of Information & Communication Technology (ICT)",
      "Department of Engineering Technology (ET)",
      "Department of Biosystems Technology (BST)"
    ],
    "Faculty of Applied Sciences": [
      "Department of Physical Science",
      "Department of Biological Science",
      "Department of Computing"
    ],
    "Faculty of Agriculture": [
      "Department of Crop Science",
      "Department of Animal Science",
      "Department of Agricultural Economics"
    ],
    "Faculty of Medicine / Health Sciences": [
      "Department of Nursing",
      "Department of Pharmacy",
      "Department of Medical Laboratory Science (MLS)"
    ],
    "Faculty of Management Studies": [
      "Department of Accountancy & Finance",
      "Department of Business Management",
      "Department of Marketing"
    ]
  };

  // Bind change events to all Faculty select elements in the document
  document.querySelectorAll('select[name="Faculty"]').forEach(function(facultySelect) {
    const form = facultySelect.closest('form');
    const deptSelect = form.querySelector('select[name="Department"]');
    
    facultySelect.addEventListener('change', function() {
      const selectedFaculty = facultySelect.value;
      const depts = facultyData[selectedFaculty] || [];
      
      // Preserve current selection if possible
      const currentDept = deptSelect.value;
      
      // Clear all options except the placeholder
      deptSelect.innerHTML = '<option value="">Select Department</option>';
      
      depts.forEach(function(dept) {
        const option = document.createElement('option');
        option.value = dept;
        option.textContent = dept;
        deptSelect.appendChild(option);
      });
      
      // Restore department value if it matches the new options list
      if (depts.includes(currentDept)) {
        deptSelect.value = currentDept;
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
