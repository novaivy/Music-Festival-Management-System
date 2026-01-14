<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'judge') {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
$full_name = $_SESSION['full_name'];

// Get pending approved registrations
$registrations = $conn->query("
    SELECT r.reg_id, r.performance_title, u.full_name, c.class_name
    FROM registration r
    JOIN users u ON r.user_id = u.user_id
    JOIN classes c ON r.class_id = c.class_id
    WHERE r.status = 'Approved'
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Judge Dashboard</title>
    <style>
        body { font-family: Arial; background: #eceff1; padding:20px; }
        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>

<h2>Welcome Judge, <?php echo $full_name; ?></h2>

<h3>Approved Performances Awaiting Scores</h3>

<table>
    <tr>
        <th>Participant</th>
        <th>Class</th>
        <th>Performance Title</th>
        <th>Action</th>
    </tr>

<?php while($row = $registrations->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $row['full_name']; ?></td>
        <td><?php echo $row['class_name']; ?></td>
        <td><?php echo $row['performance_title']; ?></td>
        <td><a href="score.php?reg_id=<?php echo $row['reg_id']; ?>">Score</a></td>
    </tr>
<?php } ?>
</table>

<br>
<a href="../auth/logout.php">Logout</a>

</body>
</html>