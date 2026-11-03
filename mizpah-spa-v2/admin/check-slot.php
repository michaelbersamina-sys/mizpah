<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Manila');

function respondAvailability($available, $remaining) {
    echo json_encode([
        'available' => (bool)$available,
        'remaining' => (int)$remaining
    ]);
    exit;
}

function durationToMinutes($duration) {
    $duration = strtolower(trim((string)$duration));

    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:hours?|hrs?)/i', $duration, $m)) {
        return (int)round((float)$m[1] * 60);
    }
    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:minutes?|mins?)/i', $duration, $m)) {
        return (int)round((float)$m[1]);
    }
    if (is_numeric($duration)) {
        $n = (float)$duration;
        return $n > 3 ? (int)$n : (int)round($n * 60);
    }
    return 60;
}

function operatingMinutes($time, $openingHour) {
    if (!preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim((string)$time), $m)) {
        return null;
    }
    $hour = (int)$m[1];
    $minute = (int)$m[2];
    if ($hour > 23 || $minute > 59) return null;

    // Times after midnight belong to the operating night that began on the selected date.
    if ($hour < $openingHour) $hour += 24;
    return $hour * 60 + $minute;
}

$date = trim($_GET['date'] ?? '');
$time = trim($_GET['time'] ?? '');
$duration = trim($_GET['duration'] ?? '1 Hour');
$roomType = trim($_GET['room_type'] ?? '');
$pax = isset($_GET['pax']) ? (int)$_GET['pax'] : 1;

$dateObject = DateTime::createFromFormat('!Y-m-d', $date);
$dateErrors = DateTime::getLastErrors();
$validDate = $dateObject !== false
    && $dateObject->format('Y-m-d') === $date
    && ($dateErrors === false
        || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0));

if (!$validDate || $time === '' || !in_array($roomType, ['Single Room', 'Couple Room'], true) || $pax < 1) {
    respondAvailability(false, 0);
}
if (($roomType === 'Single Room' && $pax > 4) || ($roomType === 'Couple Room' && $pax !== 2)) {
    respondAvailability(false, 0);
}

// Monday-Friday opens at 3 PM; Saturday-Sunday opens at 1 PM; closes at 3 AM.
$dayOfWeek = (int)$dateObject->format('N');
$openingHour = ($dayOfWeek >= 6) ? 13 : 15;
$requestedStart = operatingMinutes($time, $openingHour);
$requestedDuration = durationToMinutes($duration);

if ($requestedStart === null || $requestedDuration <= 0
    || $requestedStart < ($openingHour * 60)
    || $requestedStart + $requestedDuration > (27 * 60)) {
    respondAvailability(false, 0);
}
$requestedEnd = $requestedStart + $requestedDuration;

$sql = "
    SELECT booking_time, duration, COALESCE(NULLIF(pax, 0), 1) AS pax
    FROM bookings
    WHERE booking_date = ?
      AND room_type = ?
      AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('cancelled', 'completed')
";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    respondAvailability(false, 0);
}
mysqli_stmt_bind_param($stmt, 'ss', $date, $roomType);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    respondAvailability(false, 0);
}

$result = mysqli_stmt_get_result($stmt);
$occupiedGuests = 0;
$coupleRoomOccupied = false;

while ($booking = mysqli_fetch_assoc($result)) {
    $bookingStart = operatingMinutes($booking['booking_time'], $openingHour);
    $bookingDuration = durationToMinutes($booking['duration'] ?? '1 Hour');
    if ($bookingStart === null || $bookingDuration <= 0) continue;

    $bookingEnd = $bookingStart + $bookingDuration;
    $overlaps = ($requestedStart < $bookingEnd && $bookingStart < $requestedEnd);
    if (!$overlaps) continue;

    if ($roomType === 'Couple Room') {
        $coupleRoomOccupied = true;
        break;
    }
    $occupiedGuests += max(1, (int)$booking['pax']);
}
mysqli_stmt_close($stmt);

if ($roomType === 'Couple Room') {
    $remaining = $coupleRoomOccupied ? 0 : 1;
    respondAvailability(!$coupleRoomOccupied, $remaining);
}

$remaining = max(0, 4 - $occupiedGuests);
respondAvailability($remaining >= $pax, $remaining);
