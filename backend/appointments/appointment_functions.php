<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/time_slots.php';  

const APPOINTMENT_STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

function formatAppointmentId(int $id): string
{
    return '#' . $id;
}

function getAppointmentCounts(PDO $pdo): array
{
    $row = $pdo->query(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(status = 'pending'), 0)   AS pending,
                COALESCE(SUM(status = 'confirmed'), 0) AS confirmed
         FROM appointments"
    )->fetch();

    return [
        'total'     => (int) $row['total'],
        'pending'   => (int) $row['pending'],
        'confirmed' => (int) $row['confirmed'],
    ];
}

function appointmentFilter(string $search, string $status): array
{
    $where  = [];
    $params = [];

    if ($search !== '') {
        $like     = '%' . $search . '%';
        $where[]  = '(u.full_name LIKE ? OR p.pet_name LIKE ?)';
        $params[] = $like;
        $params[] = $like;
    }
    if ($status !== '' && in_array($status, APPOINTMENT_STATUSES, true)) {
        $where[]  = 'a.status = ?';
        $params[] = $status;
    }

    $sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    return [$sql, $params];
}

function countAppointments(PDO $pdo, string $search = '', string $status = ''): int
{
    [$where, $params] = appointmentFilter($search, $status);

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM appointments a
         JOIN pets  p ON p.pet_id  = a.pet_id
         JOIN users u ON u.user_id = p.user_id
         $where"
    );
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function getAppointments(PDO $pdo, string $search, string $status, int $limit, int $offset): array
{
    [$where, $params] = appointmentFilter($search, $status);

    $stmt = $pdo->prepare(
        "SELECT a.appointment_id, a.appointment_date, a.time_slot, a.status, a.booked_price, a.notes,
                p.pet_name, p.pet_type, p.breed,
                u.full_name, u.contact_number,
                s.service_name
         FROM appointments a
         JOIN pets     p ON p.pet_id     = a.pet_id
         JOIN users    u ON u.user_id    = p.user_id
         JOIN services s ON s.service_id = a.service_id
         $where
         ORDER BY a.appointment_date DESC,
                  STR_TO_DATE(a.time_slot, '%h:%i %p') ASC,
                  a.appointment_id DESC
         LIMIT ? OFFSET ?"
    );

    $i = 1;
    foreach ($params as $value) {
        $stmt->bindValue($i++, $value);
    }
    $stmt->bindValue($i++, $limit,  PDO::PARAM_INT);
    $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function updateAppointmentStatus(PDO $pdo, int $id, string $status): bool
{
    if (!in_array($status, APPOINTMENT_STATUSES, true)) {
        return false;
    }
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    $stmt->execute([$status, $id]);
    return true;
}


// How many appointments were booked since a moment in time, e.g. "+4 this week".
// $since looks like '2026-10-20 00:00:00'.
function countBookedSince(PDO $pdo, string $since): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE created_at >= ?");
    $stmt->execute([$since]);
    return (int) $stmt->fetchColumn();
}

// The most recently booked appointments (for the dashboard), with the customer's email.
function getRecentAppointments(PDO $pdo, int $limit = 4): array
{
    $stmt = $pdo->prepare(
        "SELECT a.appointment_id, a.appointment_date, a.time_slot, a.status, a.booked_price, a.notes,
                p.pet_name, p.pet_type, p.breed,
                u.full_name, u.email, u.contact_number,
                s.service_name
         FROM appointments a
         JOIN pets     p ON p.pet_id     = a.pet_id
         JOIN users    u ON u.user_id    = p.user_id
         JOIN services s ON s.service_id = a.service_id
         ORDER BY a.appointment_id DESC
         LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}


function createWalkInAppointment(PDO $pdo, array $data): int
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT price FROM services WHERE service_id = ? AND is_active = 1");
        $stmt->execute([(int) $data['service_id']]);
        $price = $stmt->fetchColumn();
        if ($price === false) {
            throw new RuntimeException('That service is not available anymore. Please pick another one.');
        }

        if (!isSlotAvailable($pdo, $data['appointment_date'], $data['time_slot'])) {
            throw new RuntimeException('That time slot is already taken. Please pick another time.');
        }

        $userId = null;
        if ($data['email'] !== '') {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $found = $stmt->fetchColumn();
            if ($found !== false) {
                $userId = (int) $found;
            }
        }
        if ($userId === null) {
            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, password, contact_number, role)
                 VALUES (?, ?, NULL, ?, 'customer')"
            );
            $stmt->execute([
                $data['customer_name'],
                $data['email'] !== '' ? $data['email'] : null,
                $data['phone'],
            ]);
            $userId = (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO pets (user_id, pet_name, pet_type, breed) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $data['pet_name'],
            $data['pet_type'],
            $data['breed'] !== '' ? $data['breed'] : null,
        ]);
        $petId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "INSERT INTO appointments (pet_id, service_id, appointment_date, time_slot, notes, status, booked_price)
             VALUES (?, ?, ?, ?, ?, 'confirmed', ?)"
        );
        $stmt->execute([
            $petId,
            (int) $data['service_id'],
            $data['appointment_date'],
            $data['time_slot'],
            $data['notes'] !== '' ? $data['notes'] : null,
            $price,
        ]);
        $appointmentId = (int) $pdo->lastInsertId();

        $pdo->commit();
        return $appointmentId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}