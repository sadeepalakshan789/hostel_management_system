<?php
/**
 * Auth guard - include at the very top of every protected page.
 * Starts the session, ensures the user is logged in, and loads shared includes.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['user_id'])) {
    redirect('login.php');
}
