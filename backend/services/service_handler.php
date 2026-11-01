<?php
// Nakakarecieved add / ediit / delete from the admin_services.php sa frontend
require_once __DIR__ . '/../auth/admin_guard.php';
require_once __DIR__ . '/service_functions.php';

const BACK_TO_PAGE = '../../frontend/admin/admin_services.php';

// saves the message then babalik
function backToPage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: ' . BACK_TO_PAGE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BACK_TO_PAGE);
    exit;
}

$action   = $_POST['action'] ?? '';
$id       = (int) ($_POST['service_id'] ?? 0);
$name     = trim($_POST['service_name'] ?? '');
$desc     = trim($_POST['description'] ?? '');
$priceRaw = trim($_POST['price'] ?? '');

try {
    if ($action === 'add' || $action === 'edit') {
        if ($name === '' || $desc === '' || $priceRaw === '') {
            backToPage('error', 'Please fill in the service name, description, and price.');
        }
        if (mb_strlen($name) > 100) {
            backToPage('error', 'Service name must be 100 characters or fewer.');
        }
        if (!is_numeric($priceRaw) || (float) $priceRaw < 0 || (float) $priceRaw > 99999999.99) {
            backToPage('error', 'Price must be a number that is 0 or higher.');
        }
        $price = round((float) $priceRaw, 2);

        if ($action === 'add') {
            createService($pdo, $name, $desc, $price);
            backToPage('success', '"' . $name . '" was added.');
        }

        if ($id <= 0) {
            backToPage('error', 'That service could not be found.');
        }
        updateService($pdo, $id, $name, $desc, $price);
        backToPage('success', '"' . $name . '" was updated.');
    }

    if ($action === 'hide') {
        if ($id <= 0) {
            backToPage('error', 'That service could not be found.');
        }
        hideService($pdo, $id);
        backToPage('success', 'The service was removed from the list. Past appointments keep their records.');
    }

    backToPage('error', 'Unknown action.');
} catch (PDOException $e) {
    backToPage('error', 'The database could not save that change. Please try again.');
}