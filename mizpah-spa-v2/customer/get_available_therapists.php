```php
<?php
/**
 * Mizpah Wellness Spa
 * Check therapist availability based on booking date, time, and duration.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';

date_default_timezone_set('Asia/Manila');

/**
 * Convert booking duration to minutes.
 * Supports examples:
 * 1hr, 1 Hour, 1.5hr, 2 hours, 90 minutes,
 * 1hr and 45mins, and numeric minute values.
 */
function getDurationMinutes($duration)
{
    $duration = strtolower(trim((string)$duration));

    if ($duration === '') {
        return 60;
    }

    // Example: "1hr and 45mins"
    if (
        preg_match('/(\d+(?:\.\d+)?)\s*(?:hours?|hrs?)/i', $duration, $hours) &&
        preg_match('/(\d+)\s*(?:minutes?|mins?)/i', $duration, $minutes)
    ) {
        return (int) round(((float)$hours[1] * 60) + (int)$minutes[1]);
    }

    // Example: "1.5hr", "2 hours"
    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:hours?|hrs?)/i', $duration, $match)) {
        return (int) round((float)$match[1] * 60);
    }

    // Example: "90 minutes", "45 mins"
    if (preg_match('/(\d+)\s*(?:minutes?|mins?)/i', $duration, $match)) {
        return (int)$match[1];
    }

    // Numeric values: values up to 3 are treated as hours;
    // values greater than 3 are treated as minutes.
    if (is_numeric($duration)) {
        $value = (float)$duration;

        return $value > 3
            ? (int)$value
            : (int)round($value * 60);
    }

    // Fallback for unrecognized duration formats.
    return 60;
}

/**
 * Convert a booking date and time into a timestamp.
 * Supports 24-hour values and AM/PM time values.
 */
function getBookingTimestamp($date, $time)
{
    $timestamp = strtotime($date . ' ' . $time);

    return ($timestamp === false) ? null : $timestamp;
}

try {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Database connection is unavailable.');
    }

    $date = trim($_GET['date'] ?? '');
    $time = trim($_GET['time'] ?? '');
    $duration = trim($_GET['duration'] ?? '1 Hour');

    // Validate the requested date.
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $date
    ) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid booking date.'
        ]);
        exit;
    }

    // Validate the requested time.
    if (
        !preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
            $time
        ) &&
        !preg_match(
            '/^(0?[1-9]|1[0-2]):[0-5]\d\s*(AM|PM)$/i',
            $time
        )
    ) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid booking time.'
        ]);
        exit;
    }

    $startTimestamp = getBookingTimestamp($date, $time);
    $durationMinutes = getDurationMinutes($duration);

    if ($startTimestamp === null || $durationMinutes <= 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid booking schedule or duration.'
        ]);
        exit;
    }

    $endTimestamp = $startTimestamp + ($durationMinutes * 60);

    /*
     * Get active therapists only.
     * Keep the response format compatible with the existing frontend:
     * id, name, available.
     */
    $therapistQuery = "
        SELECT id, name
        FROM therapists
        WHERE status = 'Active'
        ORDER BY name ASC
    ";

    $therapistResult = mysqli_query($conn, $therapistQuery);

    if (!$therapistResult) {
        throw new Exception('Unable to retrieve therapists.');
    }

    $therapists = [];

    while ($therapist = mysqli_fetch_assoc($therapistResult)) {
        $therapists[(int)$therapist['id']] = [
            'id' => (int)$therapist['id'],
            'name' => $therapist['name'],
            'available' => true
        ];
    }

    if (empty($therapists)) {
        echo json_encode([]);
        exit;
    }

    /*
     * Check bookings on the selected date and the previous date.
     * The previous date matters for bookings that continue past midnight.
     *
     * Completed and Cancelled bookings do not block availability.
     */
    $previousDate = date(
        'Y-m-d',
        strtotime($date . ' -1 day')
    );

    $bookingQuery = "
        SELECT
            b.id,
            b.booking_date,
            b.booking_time,
            b.duration,
            b.status,
            b.therapist_id,
            bt.therapist_id AS assigned_therapist_id
        FROM bookings b
        LEFT JOIN booking_therapists bt
            ON bt.booking_id = b.id
        WHERE b.booking_date IN (?, ?)
          AND b.status NOT IN ('Cancelled', 'Completed')
    ";

    $bookingStatement = mysqli_prepare($conn, $bookingQuery);

    if (!$bookingStatement) {
        throw new Exception('Unable to check existing bookings.');
    }

    mysqli_stmt_bind_param(
        $bookingStatement,
        'ss',
        $date,
        $previousDate
    );

    mysqli_stmt_execute($bookingStatement);

    $bookingResult = mysqli_stmt_get_result($bookingStatement);

    $busyTherapists = [];

    while ($booking = mysqli_fetch_assoc($bookingResult)) {
        $bookingStart = getBookingTimestamp(
            $booking['booking_date'],
            $booking['booking_time']
        );

        if ($bookingStart === null) {
            continue;
        }

        $existingDuration = getDurationMinutes(
            $booking['duration'] ?? '1 Hour'
        );

        $bookingEnd = $bookingStart + ($existingDuration * 60);

        /*
         * Overlap condition:
         * Existing start < requested end
         * AND existing end > requested start
         */
        $overlaps = (
            $bookingStart < $endTimestamp &&
            $bookingEnd > $startTimestamp
        );

        if (!$overlaps) {
            continue;
        }

        $primaryTherapistId = (int)(
            $booking['therapist_id'] ?? 0
        );

        $assignedTherapistId = (int)(
            $booking['assigned_therapist_id'] ?? 0
        );

        if ($primaryTherapistId > 0) {
            $busyTherapists[$primaryTherapistId] = true;
        }

        if ($assignedTherapistId > 0) {
            $busyTherapists[$assignedTherapistId] = true;
        }
    }

    mysqli_stmt_close($bookingStatement);

    /*
     * Mark therapists with overlapping bookings as unavailable.
     */
    foreach ($therapists as $therapistId => &$therapist) {
        if (isset($busyTherapists[$therapistId])) {
            $therapist['available'] = false;
        }
    }

    unset($therapist);

    echo json_encode(
        array_values($therapists),
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $e) {
    http_response_code(500);

    // Keep technical database details out of the public response.
    echo json_encode([
        'error' => 'Unable to check therapist availability. Please try again.'
    ]);
}
?>
```