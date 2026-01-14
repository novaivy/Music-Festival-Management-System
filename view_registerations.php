<?php
require_once 'config.php';
require_once 'functions.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') redirect('login.php');

$res = $conn->query("SELECT r.reg_id, u.full_name, c.class_name, r.performance_title, r.status, r.reg_date
                     FROM registration r
                     JOIN users u ON r.user_id = u.user_id
                     JOIN classes c ON r.class_id = c.class_id
                     ORDER BY r.reg_date DESC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Registrations - Music Festival</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
  <h1>📋 All Registrations</h1>
</header>

<nav>
  <ul>
    <li><a href="admin_dashboard.php">Back to Dashboard</a></li>
    <li><a href="manage_classes.php">Manage Classes</a></li>
    <li><a href="upload_result.php">Upload Results</a></li>
    <li><a href="logout.php">Logout (<?= esc($_SESSION['full_name']) ?>)</a></li>
  </ul>
</nav>

<div class="container">
  <section>
    <h2>Festival Registrations</h2>
    <p>Review and manage all participant registrations for the music festival.</p>

    <div class="form-group" style="max-width: 300px;">
      <label for="searchInput">Search Registrations</label>
      <input type="text" id="searchInput" placeholder="Search by name, class, or performance..." 
             onkeyup="filterTable('searchInput', 'regsTable')">
    </div>

    <?php if ($res->num_rows > 0): ?>
      <div class="table-responsive">
        <table id="regsTable">
          <thead>
            <tr>
              <th>Reg ID</th>
              <th>Participant Name</th>
              <th>Class</th>
              <th>Performance Title</th>
              <th>Status</th>
              <th>Registered Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $res->data_seek(0);
            while($r = $res->fetch_assoc()): 
            ?>
              <tr>
                <td>
                  <span class="badge badge-primary"><?= $r['reg_id'] ?></span>
                </td>
                <td>
                  <strong><?= esc($r['full_name']) ?></strong>
                </td>
                <td>
                  <span class="badge badge-info"><?= esc($r['class_name']) ?></span>
                </td>
                <td><?= esc($r['performance_title']) ?></td>
                <td>
                  <span class="badge badge-<?= 
                    ($r['status'] === 'Approved') ? 'success' : 
                    ($r['status'] === 'Pending') ? 'warning' : 'danger'
                  ?>">
                    <?= esc($r['status']) ?>
                  </span>
                </td>
                <td>
                  <?= date('M d, Y', strtotime($r['reg_date'])) ?>
                </td>
                <td>
                  <button class="btn btn-secondary btn-sm" onclick="viewDetails(<?= $r['reg_id'] ?>, '<?= esc($r['full_name']) ?>')">
                    👁️ View
                  </button>
                  <a href="upload_result.php?reg_id=<?= $r['reg_id'] ?>" class="btn btn-success btn-sm">
                    ⭐ Score
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-4" style="text-align: center;">
        <button class="btn btn-secondary btn-sm" onclick="exportTableToCSV('regsTable', 'registrations.csv')">
          📥 Export to CSV
        </button>
        <button class="btn btn-secondary btn-sm" onclick="window.print()">
          🖨️ Print List
        </button>
      </div>
    <?php else: ?>
      <div class="alert info" style="text-align: center; padding: 2rem;">
        <h4>No Registrations Yet</h4>
        <p>Registrations will appear here once participants sign up for classes.</p>
      </div>
    <?php endif; ?>

    <div class="mt-5 p-4" style="background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--secondary-color);">
      <h4>📊 Registration Statistics</h4>
      <div class="row mt-3">
        <div class="stat-box" style="border-top-color: var(--secondary-color);">
          <div class="stat-label">Total Registrations</div>
          <div class="stat-number" style="color: var(--secondary-color);"><?= $res->num_rows ?></div>
        </div>
      </div>
    </div>
  </section>
</div>

<footer>&copy; <?= date('Y') ?> Music Festival System</footer>

<script src="assets/js/scripts.js"></script>
<script>
function viewDetails(regId, participantName) {
  alert('Registration ID: ' + regId + '\nParticipant: ' + participantName);
}
</script>
</body>
</html>