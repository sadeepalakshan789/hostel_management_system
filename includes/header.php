<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' - ' : '' ?>Hostel Management System</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark topbar px-3">
  <button class="btn btn-link text-white d-md-none" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
  <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-building-shield me-2"></i>Hostel Management System</a>
  <div class="ms-auto d-flex align-items-center text-white">
    <span class="me-3 d-none d-sm-inline"><i class="fa-solid fa-user-circle me-1"></i><?= clean($_SESSION['full_name'] ?? 'Admin') ?></span>
    <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-right-from-bracket me-1"></i>Logout</a>
  </div>
</nav>
<div class="app-wrapper">
