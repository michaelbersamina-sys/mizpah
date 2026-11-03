<?php
session_start();
date_default_timezone_set('Asia/Manila');

include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

function bookingError(string $message): void {
    http_response_code(400);
    exit('BOOKING ERROR: ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') .
        '<br><a href="walkin-booking.php">Go back to walk-in booking</a>');
}

function durationToMinutes(string $duration): int {
    $value = strtolower(trim($duration));
    if (preg_match('/(?:^|\D)2\s*(?:hr|hour)/', $value)) return 120;
    if (preg_match('/(?:^|\D)1\.5\s*(?:hr|hour)/', $value) || strpos($value, '90 min') !== false) return 90;
    if (preg_match('/(?:^|\D)1\s*(?:hr|hour)/', $value)) return 60;
    return 60;
}

function toOperatingMinutes(string $time, int $openingHour): ?int {
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $match)) return null;
    $hour = (int)$match[1];
    $minute = (int)$match[2];
    if ($hour < $openingHour) $hour += 24;
    return $hour * 60 + $minute;
}

$roomType = trim($_POST['room_type'] ?? '');
$serviceId = (int)($_POST['service_id'] ?? 0);
$service = trim($_POST['service'] ?? '');
$duration = trim($_POST['duration'] ?? '');
$date = trim($_POST['booking_date'] ?? '');
$time = trim($_POST['booking_time'] ?? '');
$pax = (int)($_POST['pax'] ?? 1);
$payment = trim($_POST['payment_method'] ?? 'Cash');
$notes = trim($_POST['notes'] ?? '');
$addons = trim($_POST['addons'] ?? '');
$therapistId = (int)($_POST['therapist'] ?? 0);
$therapistIds = $_POST['therapist_ids'] ?? [];
if (!is_array($therapistIds)) $therapistIds = [];
$therapistIds = array_values(array_unique(array_filter(array_map('intval', $therapistIds), static fn($id) => $id > 0)));
if (!$therapistIds && $therapistId > 0) $therapistIds = [$therapistId];
$therapistId = $therapistIds[0] ?? 0;

if ($roomType === 'Couple Room') {
    $pax = 2;
} elseif ($roomType !== 'Single Room') {
    bookingError('Please select a valid room type.');
}
if ($pax < 1 || $pax > 4) bookingError('Guest count must be between 1 and 4.');
if (!in_array($payment, ['Cash', 'GCash'], true)) bookingError('Invalid payment method.');
if ($serviceId <= 0 || $service === '' || $duration === '' || $date === '' || $time === '') {
    bookingError('Please complete the service, duration, date, and time.');
}

$guestNames = $_POST['guest_name'] ?? [];
$guestPhones = $_POST['guest_phone'] ?? [];
if (!is_array($guestNames) || count($guestNames) !== $pax || !is_array($guestPhones)) {
    bookingError('Please provide a name for every guest and a contact number for Guest 1.');
}

$guests = [];
for ($i = 0; $i < $pax; $i++) {
    $name = trim((string)($guestNames[$i] ?? ''));
    // Only Guest 1 requires a contact number; other guests may not have phone fields.
    $phone = $i === 0 ? trim((string)($guestPhones[0] ?? '')) : '';
    if ($name === '') {
        bookingError('Please enter a name for every guest.');
    }
    if ($i === 0 && $phone === '') {
        bookingError('Please enter Guest 1\'s contact number.');
    }
    $guests[] = ['name' => $name, 'phone' => $phone];
}

$dateObject = DateTime::createFromFormat('!Y-m-d', $date);
if (!$dateObject || $dateObject->format('Y-m-d') !== $date) bookingError('Invalid appointment date.');
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) bookingError('Invalid time format. Please enter a time such as 2:17 PM.');

$today = date('Y-m-d');
if ($date < $today) {
    bookingError('That appointment date has already passed. Choose today or a future date.');
}

$openingHour = ((int)$dateObject->format('N') >= 6) ? 13 : 15;
$appointmentDate = $date;
if ((int)substr($time, 0, 2) < $openingHour) {
    $nextDate = clone $dateObject;
    $nextDate->modify('+1 day');
    $appointmentDate = $nextDate->format('Y-m-d');
}
if (strtotime($appointmentDate . ' ' . $time) <= time()) {
    bookingError('That appointment time has already passed. Choose a future time.');
}


$startMinute = toOperatingMinutes($time, $openingHour);
if ($startMinute === null) bookingError('Invalid appointment time.');
$endMinute = $startMinute + durationToMinutes($duration);
if ($startMinute < $openingHour * 60 || $startMinute > 26 * 60 || $endMinute > 27 * 60) {
    bookingError('The selected time is outside operating hours.');
}

// Recheck room availability on the server, excluding cancelled/completed bookings.
// Match the availability endpoint: check only bookings for the same room type.
// Single Room has capacity for 4 guests total; Couple Room is one room for 2 guests.
$availability = $conn->prepare("SELECT booking_time, duration, pax FROM bookings WHERE booking_date = ? AND room_type = ? AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('cancelled','completed')");
if (!$availability) bookingError('Could not verify room availability.');
$availability->bind_param('ss', $date, $roomType);
$availability->execute();
$rows = $availability->get_result();
$occupiedGuests = 0;
$roomConflict = false;
while ($row = $rows->fetch_assoc()) {
    $existingTime = trim((string)$row['booking_time']);
    // Accept stored HH:MM or HH:MM:SS values.
    if (preg_match('/^(\d{1,2}:\d{2})(?::\d{2})?$/', $existingTime, $timeMatch)) {
        $existingTime = $timeMatch[1];
    }
    $existingStart = toOperatingMinutes($existingTime, $openingHour);
    if ($existingStart === null) continue;
    $existingEnd = $existingStart + durationToMinutes((string)$row['duration']);
    $overlaps = $startMinute < $existingEnd && $endMinute > $existingStart;
    if (!$overlaps) continue;

    if ($roomType === 'Couple Room') {
        $roomConflict = true;
        break;
    }
    $occupiedGuests += max(1, (int)$row['pax']);
}
$availability->close();
if ($roomConflict || ($roomType === 'Single Room' && $occupiedGuests + $pax > 4)) {
    bookingError('That time is no longer available. Please choose another time.');
}

// Use the selected duration's database price, not the browser-submitted price.
$priceQuery = $conn->prepare('SELECT price FROM service_durations WHERE service_id = ? AND duration = ? LIMIT 1');
if (!$priceQuery) bookingError('Could not verify service pricing.');
$priceQuery->bind_param('is', $serviceId, $duration);
$priceQuery->execute();
$priceRow = $priceQuery->get_result()->fetch_assoc();
$priceQuery->close();
if (!$priceRow) bookingError('The selected service duration was not found. Please select it again.');
$totalPrice = (float)$priceRow['price'] * $pax; // Service price is charged per guest.

if ($addons !== '') {
    $addonNames = array_values(array_unique(array_filter(array_map('trim', explode(',', $addons)), static fn($v) => $v !== '')));
    foreach ($addonNames as $addonName) {
        $addonQuery = $conn->prepare("SELECT price FROM services WHERE service_name = ? AND category = 'Add-ons' LIMIT 1");
        if (!$addonQuery) bookingError('Could not verify add-on pricing.');
        $addonQuery->bind_param('s', $addonName);
        $addonQuery->execute();
        $addonRow = $addonQuery->get_result()->fetch_assoc();
        $addonQuery->close();
        if (!$addonRow) bookingError('An add-on could not be verified. Please select the add-ons again.');
        $totalPrice += (float)$addonRow['price'] * $pax; // Add-ons are also charged per guest.
    }
}

$customerName = $guests[0]['name'];
$phone = $guests[0]['phone'];
$status = 'Confirmed';
$createdBy = 'admin';

$conn->begin_transaction();
try {
    $insert = $conn->prepare("INSERT INTO bookings (service_id, service, duration, price, customer_name, phone, booking_date, booking_time, pax, payment_method, notes, addons, therapist_id, room_type, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$insert) throw new Exception($conn->error);
    $insert->bind_param('issdssssisssisss', $serviceId, $service, $duration, $totalPrice, $customerName, $phone, $date, $time, $pax, $payment, $notes, $addons, $therapistId, $roomType, $status, $createdBy);
    if (!$insert->execute()) throw new Exception($insert->error);
    $bookingId = $insert->insert_id;
    $insert->close();

    $guestInsert = $conn->prepare('INSERT INTO booking_guests (booking_id, guest_number, guest_name, phone) VALUES (?, ?, ?, ?)');
    if (!$guestInsert) throw new Exception('Could not save guest details. Check that the booking_guests table exists.');
    foreach ($guests as $index => $guest) {
        $guestNumber = $index + 1;
        $guestInsert->bind_param('iiss', $bookingId, $guestNumber, $guest['name'], $guest['phone']);
        if (!$guestInsert->execute()) throw new Exception($guestInsert->error);
    }
    $guestInsert->close();

    if ($therapistIds) {
        $map = $conn->prepare("INSERT INTO booking_therapists (booking_id, therapist_id, assigned_by) VALUES (?, ?, 'admin')");
        if (!$map) throw new Exception($conn->error);
        foreach ($therapistIds as $assignedTherapistId) {
            $map->bind_param('ii', $bookingId, $assignedTherapistId);
            if (!$map->execute()) throw new Exception($map->error);
        }
        $map->close();
    }

    $conn->commit();
    header('Location: bookings.php?success=1&id=' . $bookingId);
    exit;
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Admin walk-in save error: ' . $error->getMessage());
    bookingError('The booking could not be saved. Please check the details and try again.');
}
?>
