<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Room Number *</label>
    <input type="text" name="RoomNumber" class="form-control" required maxlength="20" placeholder="e.g. A-101">
  </div>
  <div class="col-md-6">
    <label class="form-label">Block *</label>
    <input type="text" name="Block" class="form-control" required maxlength="20" placeholder="e.g. A">
  </div>
  <div class="col-md-4">
    <label class="form-label">Floor *</label>
    <input type="number" name="Floor" class="form-control" required min="0" step="1">
  </div>
  <div class="col-md-4">
    <label class="form-label">Capacity (beds) *</label>
    <input type="number" name="Capacity" class="form-control" required min="1" step="1">
  </div>
  <div class="col-md-4">
    <label class="form-label">Monthly Fee (Rs.) *</label>
    <input type="number" name="MonthlyFee" class="form-control" required min="1" step="0.01">
  </div>
  <div class="col-md-6">
    <label class="form-label">Status *</label>
    <select name="Status" class="form-select" required>
      <option value="Available">Available</option>
      <option value="Full">Full</option>
      <option value="Maintenance">Maintenance</option>
    </select>
    <div class="form-text">Available/Full is auto-calculated from occupancy unless set to Maintenance.</div>
  </div>
</div>
