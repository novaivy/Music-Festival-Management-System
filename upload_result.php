<?php
require_once 'config.php';
require_once 'functions.php';
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'judge')) redirect('login.php');

$msg = $err = null;
$reg_id = isset($_GET['reg_id']) ? intval($_GET['reg_id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_id'])) {
    $reg_id = intval($_POST['reg_id']);
    $technical_score = floatval($_POST['technical_score'] ?? 0);
    $performance_score = floatval($_POST['performance_score'] ?? 0);
    $presentation_score = floatval($_POST['presentation_score'] ?? 0);
    $score = $technical_score + $performance_score + $presentation_score;
    $position = trim($_POST['position']);
    $remarks = trim($_POST['remarks']);

    // Validate scores
    if ($technical_score < 0 || $technical_score > 40) {
        $err = "Technical score must be between 0 and 40.";
    } elseif ($performance_score < 0 || $performance_score > 30) {
        $err = "Performance score must be between 0 and 30.";
    } elseif ($presentation_score < 0 || $presentation_score > 30) {
        $err = "Presentation score must be between 0 and 30.";
    } else {
        $stmt = $conn->prepare("INSERT INTO results (reg_id, score, position, remarks, technical_score, performance_score, presentation_score) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idssiddd", $reg_id, $score, $position, $remarks, $technical_score, $performance_score, $presentation_score);
        if ($stmt->execute()) {
            $msg = "✅ Result recorded successfully!";
            $reg_id = null;
        } else {
            $err = "DB Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get registration details if reg_id is provided
$reg_details = null;
if ($reg_id) {
    $stmt = $conn->prepare("SELECT r.reg_id, u.full_name, c.class_name, r.performance_title FROM registration r JOIN users u ON r.user_id=u.user_id JOIN classes c ON r.class_id=c.class_id WHERE r.reg_id = ?");
    $stmt->bind_param("i", $reg_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reg_details = $result->fetch_assoc();
    $stmt->close();
}

// list registrations to score
$list = $conn->query("SELECT r.reg_id, u.full_name, c.class_name, r.performance_title FROM registration r JOIN users u ON r.user_id=u.user_id JOIN classes c ON r.class_id=c.class_id WHERE r.status IN ('Approved','Pending') ORDER BY c.class_name");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Result - Music Festival</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .score-input {
      max-width: 150px;
    }
    .score-display {
      font-size: 1.5rem;
      font-weight: bold;
      color: var(--primary-color);
      padding: 1rem;
      background: #f8f9fa;
      border-radius: 6px;
      text-align: center;
    }
    .guidelines {
      background: #e3f2fd;
      border-left: 4px solid var(--info-color);
      padding: 1.5rem;
      border-radius: 6px;
      margin-bottom: 2rem;
    }
  </style>
</head>
<body>
<header>
  <h1>📊 Upload Performance Result</h1>
</header>

<nav>
  <ul>
    <li><a href="judge_portal.php">Back to Portal</a></li>
    <li><a href="results.php">View Results</a></li>
    <li><a href="logout.php">Logout (<?= esc($_SESSION['full_name']) ?>)</a></li>
  </ul>
</nav>

<div class="container">
  <section>
    <?php if($msg): ?><div class="success alert"><?= esc($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="error alert"><?= esc($err) ?></div><?php endif; ?>

    <div class="guidelines">
      <h4>📋 Scoring System</h4>
      <ul style="margin: 1rem 0 0 1.5rem;">
        <li><strong>Technical Skill:</strong> 0-40 points</li>
        <li><strong>Performance Quality:</strong> 0-30 points</li>
        <li><strong>Presentation:</strong> 0-30 points</li>
        <li><strong>Total Possible Score:</strong> 100 points</li>
      </ul>
    </div>

    <?php if ($reg_details): ?>
      <h3>Recording Result for Selected Performance</h3>
      <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
          <h4 class="card-title">Performance Details</h4>
        </div>
        <div class="card-body">
          <div class="row">
            <div>
              <p><strong>Participant:</strong> <?= esc($reg_details['full_name']) ?></p>
              <p><strong>Class:</strong> <?= esc($reg_details['class_name']) ?></p>
              <p><strong>Performance:</strong> <?= esc($reg_details['performance_title']) ?></p>
            </div>
          </div>
        </div>
      </div>

      <form method="post" id="resultForm">
        <input type="hidden" name="reg_id" value="<?= $reg_details['reg_id'] ?>">

        <div class="form-group">
          <label for="technical_score">Technical Skill (0-40)</label>
          <input type="number" id="technical_score" name="technical_score" min="0" max="40" step="0.5" class="score-input" required>
        </div>

        <div class="form-group">
          <label for="performance_score">Performance Quality (0-30)</label>
          <input type="number" id="performance_score" name="performance_score" min="0" max="30" step="0.5" class="score-input" required>
        </div>

        <div class="form-group">
          <label for="presentation_score">Presentation (0-30)</label>
          <input type="number" id="presentation_score" name="presentation_score" min="0" max="30" step="0.5" class="score-input" required>
        </div>

        <div class="form-group">
          <label>Total Score</label>
          <div class="score-display" id="totalScore">0 / 100</div>
        </div>

        <div class="form-group">
          <label for="position">Position (e.g., 1st, 2nd, 3rd)</label>
          <input type="text" id="position" name="position" placeholder="e.g., 1st, 2nd, 3rd" class="score-input">
        </div>

        <div class="form-group">
          <label for="remarks">Remarks / Feedback</label>
          <textarea id="remarks" name="remarks" placeholder="Provide constructive feedback for the participant..."></textarea>
        </div>

        <button class="btn btn-primary btn-lg" type="submit">Submit Score</button>
        <a href="judge_portal.php" class="btn btn-secondary btn-lg">Cancel</a>
      </form>
    <?php else: ?>
      <h3>Select a Performance to Score</h3>
      <form method="post">
        <div class="form-group">
          <label for="reg_id">Choose Performance</label>
          <select name="reg_id" id="reg_id" required onchange="this.form.submit()">
            <option value="">-- Select a performance --</option>
            <?php while($r=$list->fetch_assoc()): ?>
              <option value="<?= $r['reg_id'] ?>">
                <?= esc($r['class_name'].' — '.$r['full_name'].' ('.$r['performance_title'].') ') ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
      </form>
    <?php endif; ?>
  </section>
</div>

<footer>&copy; <?= date('Y') ?> Music Festival</footer>

<script src="assets/js/scripts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Calculate total score dynamically
  function updateTotalScore() {
    const tech = parseFloat(document.getElementById('technical_score')?.value || 0);
    const perf = parseFloat(document.getElementById('performance_score')?.value || 0);
    const pres = parseFloat(document.getElementById('presentation_score')?.value || 0);
    const total = tech + perf + pres;
    
    const totalDisplay = document.getElementById('totalScore');
    if (totalDisplay) {
      totalDisplay.textContent = total.toFixed(1) + ' / 100';
      totalDisplay.style.color = total >= 80 ? 'var(--success-color)' : 
                                   total >= 60 ? 'var(--warning-color)' : 
                                   'var(--danger-color)';
    }
  }

  // Add event listeners
  ['technical_score', 'performance_score', 'presentation_score'].forEach(id => {
    const input = document.getElementById(id);
    if (input) {
      input.addEventListener('input', updateTotalScore);
    }
  });
});
</script>
</body>
</html>