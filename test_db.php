<?php
include('config.php');

$sql = "SHOW TABLES";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "<h2>Database Connected Successfully ✅</h2>";
    echo "<ul>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>