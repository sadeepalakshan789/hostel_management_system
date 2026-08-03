<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Registration No *</label>
    <input type="text" name="RegistrationNo" class="form-control" required maxlength="20" placeholder="e.g. ICT/2021/001">
  </div>
  <div class="col-md-6">
    <label class="form-label">Full Name *</label>
    <input type="text" name="FullName" class="form-control" required maxlength="100">
  </div>
  <div class="col-md-4">
    <label class="form-label">Gender *</label>
    <select name="Gender" class="form-select" required>
      <option value="">Select</option>
      <option value="Male">Male</option>
      <option value="Female">Female</option>
      <option value="Other">Other</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Phone *</label>
    <input type="text" name="Phone" class="form-control" required pattern="[0-9]{9,15}" title="Digits only, 9-15 characters" maxlength="15" placeholder="0771234567">
  </div>
  <div class="col-md-4">
    <label class="form-label">Email *</label>
    <input type="email" name="Email" class="form-control" required maxlength="100">
  </div>
  <div class="col-md-4">
    <label class="form-label">Faculty *</label>
    <input type="text" name="Faculty" class="form-control" required maxlength="100">
  </div>
  <div class="col-md-4">
    <label class="form-label">Department *</label>
    <input type="text" name="Department" class="form-control" required maxlength="100">
  </div>
  <div class="col-md-4">
    <label class="form-label">Academic Year *</label>
    <select name="AcademicYear" class="form-select" required>
      <option value="">Select</option>
      <option value="1st Year">1st Year</option>
      <option value="2nd Year">2nd Year</option>
      <option value="3rd Year">3rd Year</option>
      <option value="4th Year">4th Year</option>
    </select>
  </div>
</div>
