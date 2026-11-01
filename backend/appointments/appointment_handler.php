<?php
require_once __DIR__ . '/../auth/admin_guard.php';
require_once __DIR__ . '/appointment_functions.php';

const BACK_TO_PAGE   = '../../frontend/admin/admin_appointments.php';
const BACK_TO_WALKIN = '../../frontend/admin/admin_walkin.php';


function backToPage(string $type, string $message, string $to = BACK_TO_PAGE): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];

    $url = $to;
    if ($to === BACK_TO_PAGE) {
        $keep   = [];
        $search = trim($_POST['search'] ?? '');
        $status = trim($_POST['filter_status'] ?? '');
        $page   = (int) ($_POST['page'] ?? 1);
        if ($search !== '')                                $keep['search'] = $search;
        if (in_array($status, APPOINTMENT_STATUSES, true)) $keep['status'] = $status;
        if ($page > 1)                                     $keep['page']   = $page;
        $url .= $keep ? '?' . http_build_query($keep) : '';
    }

    header('Location: ' . $url);
    exit;
}

// Go back to the walk-in form with an error, and keep what the admin typed
function backToWalkin(string $message): void
{
    $_SESSION['walkin_old'] = [
        'customer_name'    => trim($_POST['customer_name'] ?? ''),
        'phone'            => trim($_POST['phone'] ?? ''),
        'email'            => trim($_POST['email'] ?? ''),
        'pet_name'         => trim($_POST['pet_name'] ?? ''),
        'pet_type'         => trim($_POST['pet_type'] ?? ''),
        'breed'            => trim($_POST['breed'] ?? ''),
        'service_id'       => (int) ($_POST['service_id'] ?? 0),
        'appointment_date' => trim($_POST['appointment_date'] ?? ''),
        'time_slot'        => trim($_POST['time_slot'] ?? ''),
        'notes'            => trim($_POST['notes'] ?? ''),
    ];
    backToPage('error', $message, BACK_TO_WALKIN);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BACK_TO_PAGE);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    // ---------- New walk-in booking ----------
    if ($action === 'walkin') {
        $data = [
            'customer_name'    => trim($_POST['customer_name'] ?? ''),
            'phone'            => trim($_POST['phone'] ?? ''),
            'email'            => trim($_POST['email'] ?? ''),
            'pet_name'         => trim($_POST['pet_name'] ?? ''),
            'pet_type'         => trim($_POST['pet_type'] ?? ''),
            'breed'            => trim($_POST['breed'] ?? ''),
            'service_id'       => (int) ($_POST['service_id'] ?? 0),
            'appointment_date' => trim($_POST['appointment_date'] ?? ''),
            'time_slot'        => trim($_POST['time_slot'] ?? ''),
            'notes'            => trim($_POST['notes'] ?? ''),
        ];

        if ($data['customer_name'] === '' || $data['phone'] === '' || $data['pet_name'] === ''
            || $data['pet_type'] === '' || $data['service_id'] <= 0
            || $data['appointment_date'] === '' || $data['time_slot'] === '') {
            backToWalkin('Please fill in the customer, pet, service, date, and time.');
        }
        if (mb_strlen($data['customer_name']) > 100 || mb_strlen($data['pet_name']) > 50 || mb_strlen($data['breed']) > 50) {
            backToWalkin('A name is too long. Customer: 100 characters, pet and breed: 50.');
        }
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $data['phone'])) {
            backToWalkin('Please enter a valid phone number (digits, spaces, + or -).');
        }
        if ($data['email'] !== '' && (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 100)) {
            backToWalkin('Please enter a valid email address, or leave it empty.');
        }
        if (!in_array($data['pet_type'], ['Dog', 'Cat'], true)) {
            backToWalkin('Please choose Dog or Cat.');
        }
        $date = DateTime::createFromFormat('Y-m-d', $data['appointment_date']);
        if (!$date || $date->format('Y-m-d') !== $data['appointment_date']) {
            backToWalkin('Please choose a valid date.');
        }
        if ($data['appointment_date'] < date('Y-m-d')) {
            backToWalkin('The date cannot be in the past.');
        }
        if (!in_array($data['time_slot'], TIME_SLOTS, true)) {
            backToWalkin('Please choose one of the available time slots.');
        }

        try {
            $newId = createWalkInAppointment($pdo, $data);
        } catch (RuntimeException $e) {
            backToWalkin($e->getMessage());   // slot taken, service hidden
        }

        unset($_SESSION['walkin_old']);
        backToPage('success', 'Walk-in booked: ' . formatAppointmentId($newId) . ' for ' . $data['customer_name']
            . ' (' . $data['pet_name'] . ') on ' . date('M j, Y', strtotime($data['appointment_date']))
            . ' at ' . $data['time_slot'] . '.');
    }

    // ---------- Edit status / Cancel ----------
    $id = (int) ($_POST['appointment_id'] ?? 0);
    if ($id <= 0) {
        backToPage('error', 'That appointment could not be found.');
    }

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if (!updateAppointmentStatus($pdo, $id, $status)) {
            backToPage('error', 'Please choose a valid status.');
        }
        backToPage('success', 'Appointment ' . formatAppointmentId($id) . ' is now ' . ucfirst($status) . '.');
    }

    if ($action === 'cancel') {
        updateAppointmentStatus($pdo, $id, 'cancelled');
        backToPage('success', 'Appointment ' . formatAppointmentId($id) . ' was cancelled. Its time slot is free again.');
    }

    backToPage('error', 'Unknown action.');
} catch (PDOException $e) {
    if ($action === 'walkin') {
        backToWalkin('The database could not save the booking. Please try again.');
    }
    backToPage('error', 'The database could not save that change. Please try again.');
}