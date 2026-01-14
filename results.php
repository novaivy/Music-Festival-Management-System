<?php
require_once 'config.php';
require_once 'functions.php';

$class_id = intval($_GET['class_id'] ?? 0);

if ($class_id > 0) {
    $stmt = $conn->prepare("SELECT u.full_name, c.class_name, res.score, res.position, res.remarks FROM results res JOIN registration r ON res.reg_id=r.reg_id JOIN users u ON r.user_id=u.user_id JOIN classes c ON r.class_id=c.class_id WHERE c.class_id = ? ORDER BY res.position ASC");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT u.full_name, c.class_name, res.score, res.position, res.remarks FROM results res JOIN registration r ON res.reg_id=r.reg_id JOIN users u ON r.user_id=u.user_id JOIN classes c ON r.class_id=c.class_id ORDER BY c.class_name, res.position");
}
$classes = $conn->query("SELECT class_id, class_name FROM classes ORDER BY class_name");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Results - Music Festival</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
  <h1>🏆 Festival Results & Leaderboard</h1>
</header>

<nav>
  <ul>
    <li><a href="index.php">Home</a></li>
    <?php if (isset($_SESSION['user_id'])): ?>
      <li><a href="<?php 
        echo ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : 
             ($_SESSION['role'] == 'judge') ? 'judge_portal.php' : 'user_dashboard.php'; 
      ?>">Dashboard</a></li>
      <li><a href="logout.php">Logout (<?= esc($_SESSION['full_name'] ?? 'User') ?>)</a></li>
    <?php else: ?>
      <li><a href="login.php">Login</a></li>
      <li><a href="register.php">Register</a></li>
    <?php endif; ?>
  </ul>
</nav>

<div class="container">
  <section>
    <h2>Performance Results</h2>
    <p>View the scores and rankings for all performances across different classes.</p>

    <div class="form-group" style="max-width: 300px;">
      <label for="classFilter">Filter by Class</label>
      <form method="get" id="filterForm">
        <select name="class_id" id="classFilter" onchange="document.getElementById('filterForm').submit()">
          <option value="0" <?= ($class_id == 0) ? 'selected' : '' ?>>All Classes</option>
          <?php 
          $classes->data_seek(0);
          while($c=$classes->fetch_assoc()): ?>
            <option value="<?= $c['class_id'] ?>" <?= ($c['class_id']==$class_id)?'selected':'' ?>>
              <?= esc($c['class_name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </form>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <div class="table-responsive">
        <table id="resultsTable">
          <thead>
            <tr>
              <th>Position</th>
              <th>Participant Name</th>
              <th>Class</th>
              <th>Score</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $position_counter = 1;
            while($row = $result->fetch_assoc()): 
            ?>
              <tr>
                <td>
                  <span class="badge badge-<?= 
                    ($row['position'] === '1st' || $position_counter === 1) ? 'success' : 
                    ($row['position'] === '2nd' || $position_counter === 2) ? 'info' : 'secondary'
                  ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                    <?= esc($row['position'] ?? '#' . $position_counter) ?>
                  </span>
                </td>
                <td>
                  <strong><?= esc($row['full_name']) ?></strong>
                </td>
                <td>
                  <span class="badge badge-primary"><?= esc($row['class_name']) ?></span>
                </td>
                <td>
                  <span class="score-display" style="display: inline-block; padding: 0.5rem 1rem; background: #f0f0f0; border-radius: 4px;">
                    <strong><?= number_format($row['score'], 2) ?></strong> / 100
                  </span>
                </td>
                <td style="max-width: 200px;"><?= esc($row['remarks'] ?? '—') ?></td>
              </tr>
            <?php 
            $position_counter++;
            endwhile; 
            ?>
          </tbody>
        </table>
      </div>

      <div class="mt-4" style="text-align: center;">
        <button class="btn btn-secondary btn-sm" onclick="exportTableToCSV('resultsTable', 'festival_results.csv')">
          📥 Export to CSV
        </button>
        <button class="btn btn-secondary btn-sm" onclick="window.print()">
          🖨️ Print Results
        </button>
      </div>
    <?php else: ?>
      <div class="alert info" style="text-align: center; padding: 2rem;">
        <h4>No Results Yet</h4>
        <p>Scoring is in progress. Check back soon for the latest results!</p>
      </div>
    <?php endif; ?>

    <div class="mt-5 p-4" style="background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--primary-color);">
      <h4>📊 Scoring Information</h4>
      <p>Performances are evaluated on the following criteria:</p>
      <ul style="margin-left: 1.5rem;">
        <li><strong>Technical Skill (40%):</strong> Precision, accuracy, and technical proficiency</li>
        <li><strong>Performance Quality (30%):</strong> Emotional expression, interpretation, and artistry</li>
        <li><strong>Presentation (30%):</strong> Stage presence, confidence, and audience engagement</li>
      </ul>
    </div>
  </section>
</div>

<footer>&copy; <?= date('Y') ?> Music Festival System</footer>

<script src="assets/js/scripts.js"></script>
</body>
</html>