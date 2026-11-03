<?php

session_start();

include __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');


/* =========================================================
   ADMIN THERAPIST AVAILABILITY
   =========================================================

   PURPOSE:
   - Used by admin/walkin-booking.php
   - Shows ALL active therapists
   - Available therapists can be selected
   - Booked therapists remain visible but are unavailable
   - Checks date + time + duration
   - Completed / Cancelled bookings do not block
   - NAME ONLY
   ========================================================= */


/* =========================================================
   GET REQUEST VALUES
   ========================================================= */

$date = trim($_GET['date'] ?? '');

$time = trim($_GET['time'] ?? '');

$durationText = trim($_GET['duration'] ?? '1 Hour');


/* =========================================================
   BASIC VALIDATION
   ========================================================= */

if ($date === '' || $time === '') {

    http_response_code(400);

    echo json_encode([
        'error' => 'Date and time are required.'
    ]);

    exit;

}


/* =========================================================
   CONVERT DURATION TO MINUTES
   ========================================================= */

function durationToMinutes($duration)
{

    $duration = strtolower(
        trim((string)$duration)
    );


    /*
       Examples supported:

       1hr
       1 hr
       1 hour
       1 hours
       1.5hr
       1.5 hr
       1.5 hours
       90 mins
       90 minutes
       1 hr 30 mins
    */


    if (
        preg_match(
            '/(\d+(?:\.\d+)?)\s*(?:hour|hours|hr|hrs)/',
            $duration,
            $hourMatch
        )
    ) {

        $minutes =
            (float)$hourMatch[1] * 60;

        /*
           Check if there is also
           a minute value.
        */

        if (
            preg_match(
                '/(\d+)\s*(?:minute|minutes|min|mins)/',
                $duration,
                $minuteMatch
            )
        ) {

            $minutes +=
                (int)$minuteMatch[1];

        }

        return (int)$minutes;

    }


    if (
        preg_match(
            '/(\d+)\s*(?:minute|minutes|min|mins)/',
            $duration,
            $minuteMatch
        )
    ) {

        return (int)$minuteMatch[1];

    }


    /*
       Fallback
       Default = 60 minutes
    */

    return 60;

}


$requestedMinutes =
    durationToMinutes(
        $durationText
    );


/* =========================================================
   VALIDATE TIME
   ========================================================= */

$requestedStart =
    strtotime(
        $date . ' ' . $time
    );


if ($requestedStart === false) {

    http_response_code(400);

    echo json_encode([
        'error' => 'Invalid date or time.'
    ]);

    exit;

}


$requestedEnd =
    $requestedStart +
    ($requestedMinutes * 60);


/* =========================================================
   GET ALL ACTIVE THERAPISTS
   =========================================================

   IMPORTANT:
   We do NOT include specialty here.

   Only:
   id
   name
   ========================================================= */

$therapistQuery = mysqli_query(
    $conn,
    "
    SELECT
        id,
        name
    FROM therapists
    WHERE status = 'Active'
    ORDER BY id ASC
    "
);


if (!$therapistQuery) {

    http_response_code(500);

    echo json_encode([
        'error' => 'Unable to load therapists.'
    ]);

    exit;

}


/* =========================================================
   GET EXISTING BOOKINGS FOR THE DATE
   =========================================================

   Only bookings that can block a therapist are included.

   Completed and Cancelled do NOT block.
   ========================================================= */

$bookingQuery = mysqli_prepare(
    $conn,
    "
    SELECT
        therapist_id,
        booking_time,
        duration,
        status
    FROM bookings
    WHERE booking_date = ?
      AND therapist_id IS NOT NULL
      AND therapist_id > 0
      AND status NOT IN ('Completed', 'Cancelled')
    "
);


if (!$bookingQuery) {

    http_response_code(500);

    echo json_encode([
        'error' => 'Unable to check bookings.'
    ]);

    exit;

}


mysqli_stmt_bind_param(
    $bookingQuery,
    's',
    $date
);


mysqli_stmt_execute(
    $bookingQuery
);


$bookingResult =
    mysqli_stmt_get_result(
        $bookingQuery
    );


/* =========================================================
   STORE BLOCKED THERAPIST IDS
   ========================================================= */

$blockedTherapists = [];


while (
    $booking =
    mysqli_fetch_assoc(
        $bookingResult
    )
) {

    $existingTherapistId =
        (int)$booking['therapist_id'];


    $existingTime =
        trim(
            $booking['booking_time']
        );


    $existingDuration =
        trim(
            $booking['duration'] ?? ''
        );


    /*
       Convert existing duration
       to minutes.
    */

    $existingMinutes =
        durationToMinutes(
            $existingDuration
        );


    /*
       Existing booking start
    */

    $existingStart =
        strtotime(
            $date . ' ' . $existingTime
        );


    if ($existingStart === false) {
        continue;
    }


    /*
       Existing booking end
    */

    $existingEnd =
        $existingStart +
        ($existingMinutes * 60);


    /*
       OVERLAP CHECK

       Existing:
       start ---------------- end

       Requested:
          start ---------------- end

       They overlap when:

       existingStart < requestedEnd
       AND
       existingEnd > requestedStart
    */

    $hasOverlap =
        (
            $existingStart < $requestedEnd
            &&
            $existingEnd > $requestedStart
        );


    if ($hasOverlap) {

        $blockedTherapists[
            $existingTherapistId
        ] = true;

    }

}


/* =========================================================
   BUILD THERAPIST LIST
   ========================================================= */

$therapists = [];


while (
    $therapist =
    mysqli_fetch_assoc(
        $therapistQuery
    )
) {

    $therapistId =
        (int)$therapist['id'];


    $therapistName =
        $therapist['name'];


    $isAvailable =
        !isset(
            $blockedTherapists[
                $therapistId
            ]
        );


    /*
       NAME ONLY

       No specialty
       No specialization
    */

    $therapists[] = [

        'id' =>
            $therapistId,

        'name' =>
            $therapistName,

        'available' =>
            $isAvailable

    ];

}


/* =========================================================
   RETURN JSON
   ========================================================= */

echo json_encode(
    $therapists
);

exit;