<?php
include("../includes/db.php");
include("../includes/auth.php");
checkLogin();

if ($_SESSION['role'] !== "school") {
    echo "Access Denied!";
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
<title>School Dashboard</title>
<link rel="stylesheet" href="../assets/css/school.css">
</head>
<body>

<h1>Welcome, <?php echo $_SESSION['name']; ?> (School)</h1>

<ul>
    <li><a href="create_team.php">Register Performance Team</a></li>
    <li><a href="view_events.php">View Events</a></li>
    <li><a href="register_event.php">Join Event</a></li>
    <li><a href="view_results.php">View Results</a></li>
</ul>

<a href="logout.php">Logout</a>

</body>
</html>