<?php
// Fixed time slot lang ang pwede
const TIME_SLOTS = ['9:00 AM', '10:30 AM', '1:00 PM', '2:30PM', '4:00 PM', '5:30 PM'];

// if cancelled returns nya yung slot
function getAvailableSlots(PDO $pdo, string $date): array
{
    $stmt = $pdo->prepare(
        "SELECT time_slot FROM appointments
         WHERE appointment_date = ? AND LOWER(status) <> 'cancelled'"
    );
    $stmt->execute([$date]);
    $taken = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return array_values(array_diff(TIME_SLOTS, $taken));
}

// True if one specific slot is still free on that date
function isSlotAvailable(PDO $pdo, string $date, string $slot): bool
{
    return in_array($slot, getAvailableSlots($pdo, $date), true);
}
