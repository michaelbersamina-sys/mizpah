
<?php
include 'includes/db.php';

$query = mysqli_query($conn, "
    SELECT * FROM ratings
    WHERE status = 'shown'
    ORDER BY id DESC
");

if (!$query) {
    error_log("Ratings query error: " . mysqli_error($conn));
    http_response_code(500);
    exit;
}

if (mysqli_num_rows($query) == 0) {
    echo "<p style='color:#aaa;'>No reviews yet.</p>";
    exit;
}

while ($row = mysqli_fetch_assoc($query)) {
    $name = htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $msg = htmlspecialchars($row['message'] ?? '', ENT_QUOTES, 'UTF-8');
    $stars = str_repeat("★", max(0, min(5, (int)($row['rating'] ?? 0))));
    $createdAt = htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8');

    echo "
        <div class='rating-chat'>
            <h4>{$name}</h4>
            <div class='stars'>{$stars}</div>
            <p>{$msg}</p>
            <small>{$createdAt}</small>
        </div>
    ";
}
?>
