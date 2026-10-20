<?php
// TEMPORARY admin check.
// para sure lang if nag wwork, wait pa kita yasminnn

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Admins only. Please log in with an admin account.');
}