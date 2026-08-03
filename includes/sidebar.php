<aside class="sidebar" id="sidebar">
  <ul class="nav flex-column">
    <li class="nav-item">
      <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
        <i class="fa-solid fa-gauge-high"></i> Dashboard
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $currentPage === 'students.php' ? 'active' : '' ?>" href="students.php">
        <i class="fa-solid fa-user-graduate"></i> Students
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $currentPage === 'rooms.php' ? 'active' : '' ?>" href="rooms.php">
        <i class="fa-solid fa-door-open"></i> Rooms
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $currentPage === 'allocations.php' ? 'active' : '' ?>" href="allocations.php">
        <i class="fa-solid fa-bed"></i> Allocations
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $currentPage === 'payments.php' ? 'active' : '' ?>" href="payments.php">
        <i class="fa-solid fa-money-bill-wave"></i> Payments
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php">
        <i class="fa-solid fa-chart-column"></i> Reports
      </a>
    </li>
  </ul>
</aside>
