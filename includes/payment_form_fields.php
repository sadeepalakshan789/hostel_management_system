<div class="row g-3">
  <div class="col-md-12">
    <label class="form-label">Student / Allocation *</label>
    <select name="AllocationID" class="form-select" required>
      <option value="">Select allocation</option>
      <?php foreach ($allocationOptions as $a): ?>
        <option value="<?= (int)$a['AllocationID'] ?>">
          <?= clean($a['FullName']) ?> (<?= clean($a['RegistrationNo']) ?>) — <?= clean($a['Block'] . '-' . $a['RoomNumber']) ?>
          <?= $a['AllocationStatus'] !== 'Active' ? ' [' . clean($a['AllocationStatus']) . ']' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Payment Month *</label>
    <input type="month" name="PaymentMonth" class="form-control" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Amount (Rs.) *</label>
    <input type="number" name="Amount" class="form-control" required min="1" step="0.01">
  </div>
  <div class="col-md-4">
    <label class="form-label">Status *</label>
    <select name="PaymentStatus" class="form-select" required>
      <option value="Paid">Paid</option>
      <option value="Pending">Pending</option>
      <option value="Overdue">Overdue</option>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Payment Date (required if Paid)</label>
    <input type="date" name="PaymentDate" class="form-control">
  </div>
</div>
