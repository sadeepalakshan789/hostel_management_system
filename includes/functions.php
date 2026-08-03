<?php
/**
 * Common helper functions
 * Student Hostel Management System
 */

/** Sanitize a plain text input */
function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Validate email format */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Validate phone: digits only, 9-15 characters */
function isValidPhone($phone) {
    return preg_match('/^[0-9]{9,15}$/', $phone) === 1;
}

/** Set a flash message shown once on next page load */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Retrieve and clear the flash message */
function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Redirect helper */
function redirect($url) {
    header("Location: $url");
    exit;
}

/** Format currency for display */
function formatMoney($amount) {
    return 'Rs. ' . number_format((float)$amount, 2);
}

/** Format date for display */
function formatDate($date) {
    if (empty($date)) return '-';
    return date('d M Y', strtotime($date));
}

/** Generate a CSRF token and store in session */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Validate a submitted CSRF token */
function verifyCsrf($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/** Badge class for allocation/payment/room statuses */
function statusBadge($status) {
    $map = [
        'Active'      => 'success',
        'Checked Out' => 'secondary',
        'Transferred' => 'info',
        'Available'   => 'success',
        'Full'        => 'danger',
        'Maintenance' => 'warning',
        'Paid'        => 'success',
        'Pending'     => 'warning',
        'Overdue'     => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return "<span class=\"badge bg-$class\">" . clean($status) . "</span>";
}
