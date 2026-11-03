
<?php
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';
$duration = $_GET['duration'] ?? '1hr';
$pax = max(1, (int)($_GET['pax'] ?? 1));
$roomType = trim((string)($_GET['room_type'] ?? 'Single Room'));
if (!in_array($roomType, ['Single Room', 'Couple Room'], true)) $roomType = 'Single Room';
$capacity = ($roomType === 'Couple Room') ? 2 : 4;

function slotDurationMinutes($duration)
{
    $d = strtolower(trim((string)$duration));

    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:hours?|hrs?)/', $d, $m)) {
        return (int)round((float)$m[1] * 60);
    }

    if (preg_match('/(\d+)\s*mins?/', $d, $m)) {
        return (int)$m[1];
    }

    if (preg_match('/(\d+(?:\.\d+)?)\s*hr/', $d, $m)) {
        return (int)round((float)$m[1] * 60);
    }

    return 60;
}

function slotStartMinutes($time)
{
    if (!preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
        return null;
    }

    $hour = (int)$m[1];
    $minute = (int)$m[2];

    if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
        return null;
    }

    return $hour * 60 + $minute;
}

if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
    !checkdate(
        (int)substr($date, 5, 2),
        (int)substr($date, 8, 2),
        (int)substr($date, 0, 4)
    ) ||
    !preg_match('/^\d{2}:\d{2}$/', $time)
) {
    echo json_encode([
        'available' => false,
        'remaining' => 0
    ]);
    exit;
}

$start = slotStartMinutes($time);
$durationMinutes = slotDurationMinutes($duration);

if ($start === null || $durationMinutes <= 0) {
    echo json_encode([
        'available' => false,
        'remaining' => 0
    ]);
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT booking_time, duration, pax, status
    FROM bookings
    WHERE booking_date = ?
      AND room_type = ?
      AND LOWER(status) NOT IN ('cancelled', 'completed')
");

if (!$stmt) {
    echo json_encode([
        'available' => false,
        'remaining' => 0
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'ss', $date, $roomType);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$occupied = 0;

while ($booking = mysqli_fetch_assoc($result)) {
    $existingStart = slotStartMinutes($booking['booking_time'] ?? '');

    if ($existingStart === null) {
        continue;
    }

    $existingDuration = slotDurationMinutes(
        $booking['duration'] ?? '1hr'
    );

    $existingEnd = $existingStart + $existingDuration;
    $selectedEnd = $start + $durationMinutes;

    if ($start < $existingEnd && $selectedEnd > $existingStart) {
        $occupied += max(1, (int)($booking['pax'] ?? 1));
    }
}

mysqli_stmt_close($stmt);

/*
 * Current capacity rule: 4 guests at overlapping times.
 */
$remaining = max(0, $capacity - $occupied);

echo json_encode([
    'available' => $occupied + $pax <= $capacity,
    'remaining' => $remaining
]);
?>
