<?php
// Detect active page script for sidebar navigation highlight
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' - ' : '' ?>Hostel Management System</title>
<!-- Bootstrap 5 CSS, FontAwesome 6 Icons, and Custom Application Styles -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<script>
  // Apply saved theme class early before paint to prevent dark mode flicker
  (function() {
    const theme = localStorage.getItem('theme') || 'light';
    if (theme === 'dark') {
      document.body.classList.add('dark-mode');
    }
  })();
</script>
<!-- Main top navigation bar -->
<nav class="navbar navbar-dark topbar px-3">
  <!-- Mobile responsive drawer toggle -->
  <button class="btn btn-link text-white d-md-none" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
  <!-- Brand logo link -->
  <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-building-shield me-2"></i>Hostel Management System</a>
  <!-- User session badge, dark/light theme switch, and logout action -->
  <div class="ms-auto d-flex align-items-center gap-2">
    <span class="topbar-user d-none d-sm-inline"><i class="fa-solid fa-user-circle me-1"></i><?= clean($_SESSION['full_name'] ?? 'Admin') ?></span>
    <button id="themeToggle" class="topbar-theme-btn d-flex align-items-center justify-content-center" title="Toggle Theme">
      <i class="fa-regular fa-moon"></i>
    </button>
    <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-right-from-bracket me-1"></i>Logout</a>
  </div>
</nav>
<!-- Main app layout container wrapper -->
<div class="app-wrapper">

