<?php
include 'includes/db.php';

$query = mysqli_query($conn,"
SELECT * FROM ratings
WHERE status='shown'
ORDER BY id DESC
");

if(mysqli_num_rows($query)==0){
    echo "<p style='color:#aaa;'>No reviews yet.</p>";
    exit;
}

while($row = mysqli_fetch_assoc($query)){

$name = htmlspecialchars($row['name']);
$msg  = htmlspecialchars($row['message']);
$stars = str_repeat("★", (int)$row['rating']);

echo "
<div class='rating-chat'>
    <h4>{$name}</h4>
    <div class='stars'>{$stars}</div>
    <p>{$msg}</p>
    <small>{$row['created_at']}</small>
</div>
";
}
?>