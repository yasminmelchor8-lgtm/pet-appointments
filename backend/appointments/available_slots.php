<?php
require_once __DIR__ . '/../auth/admin_guard.php';
require_once __DIR__ . '/appointment_functions.php';

header('Content-Type: application/json');

$date = trim($_GET['date'] ?? '');
$d    = DateTime::createFromFormat('Y-m-d', $date);

if (!$d || $d->format('Y-m-d') !== $date) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date']);
    exit;
}

try {
    echo json_encode(['date' => $date, 'available' => getAvailableSlots($pdo, $date)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}