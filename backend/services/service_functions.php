<?php
// CRUD READ and DELETE
require_once __DIR__ . '/../config/db.php';

function formatServiceId(int $id): string
{
    return '#SRV-' . str_pad((string) $id, 2, '0', STR_PAD_LEFT);
}

// READ: all service ay hindi hidden and gamit $search to find the name
function getActiveServices(PDO $pdo, string $search = ''): array
{
    $sql    = "SELECT service_id, service_name, description, price
               FROM services
               WHERE is_active = 1";
    $params = [];

    if ($search !== '') {
        $like    = '%' . $search . '%';
        $sql    .= " AND (service_name LIKE ? OR description LIKE ?";
        $params  = [$like, $like];

        if (preg_match('/^#?srv-?0*(\d+)$/i', $search, $m)) {
            $sql     .= " OR service_id = ?";
            $params[] = (int) $m[1];
        }
        $sql .= ")";
    }

    $sql .= " ORDER BY service_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function createService(PDO $pdo, string $name, string $description, float $price): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO services (service_name, description, price, is_active)
         VALUES (?, ?, ?, 1)"
    );
    $stmt->execute([$name, $description, $price]);
}

function updateService(PDO $pdo, int $id, string $name, string $description, float $price): void
{
    $stmt = $pdo->prepare(
        "UPDATE services
         SET service_name = ?, description = ?, price = ?
         WHERE service_id = ?"
    );
    $stmt->execute([$name, $description, $price, $id]);
}

// DELETE(kinda pero update) "delete = hidden but the rows stays sa loob padin" 
function hideService(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("UPDATE services SET is_active = 0 WHERE service_id = ?");
    $stmt->execute([$id]);
}