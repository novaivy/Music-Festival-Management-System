<?php
require_once 'config.php';
require_once 'functions.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'judge') redirect('login.php');

$res = $conn->query("SELECT r.reg_id, u.full_name, c.class_name, r.performance_title FROM registration r JOIN users u ON r.user_id=u.user_id JOIN classes c ON r.class_id=c.class_id ORDER BY c.class_name");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Judge Portal - Music Festival</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    header {
      background: linear-gradient(135deg, #0d6e55 0%, #0a5c49 100%);
      color: white;
      padding: 2rem;
    }
    
    nav {
      background: rgba(255, 255, 255, 0.95);
      padding: 1rem 2rem;
      border-bottom: 2px solid #0d6e55;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    nav a {
      color: #0d6e55;
      padding: 0.75rem 1.5rem;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      display: inline-block;
      transition: all 0.3s ease;
    }
    
    nav a:hover {
      background: #0d6e55;
      color: white;
    }
  </style>
</head>
<body>
<header>
  <h1>🎵 Judge Portal</h1>
</header>

<nav>
  <ul>
    <li><a href="judge_portal.php">Performances to Score</a></li>
    <li><a href="results.php">View Results</a></li>
    <li><a href="logout.php">Logout (<?= isset($_SESSION['full_name']) ? esc($_SESSION['full_name']) : 'Judge' ?>)</a></li>
  </ul>
</nav>

<div class="container">
  <section>
    <h2>Welcome Back, Judge! 👨‍⚖️</h2>
    <p>Review and score the performances listed below. Your fair evaluation helps determine the winners.</p>

    <h3 class="mt-4">📋 Performances Awaiting Your Score</h3>
    
    <?php if ($res->num_rows > 0): ?>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Reg ID</th>
              <th>Participant</th>
              <th>Class</th>
              <th>Performance Title</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r = $res->fetch_assoc()): ?>
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
                  <a class="btn btn-primary btn-sm" href="upload_result.php?reg_id=<?= $r['reg_id'] ?>">
                    ⭐ Score Now
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert info" style="text-align: center; padding: 2rem;">
        <h4>No Performances Awaiting Scores</h4>
        <p>All performances have been scored. Check the results page to view all evaluations.</p>
        <a href="results.php" class="btn btn-secondary mt-3">View Results</a>
      </div>
    <?php endif; ?>

    <div class="mt-5 p-4" style="background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--info-color);">
      <h4>📝 Scoring Guidelines</h4>
      <ul style="margin-left: 1.5rem;">
        <li><strong>Technical Skill (0-40):</strong> Evaluate the technical proficiency and execution</li>
        <li><strong>Performance Quality (0-30):</strong> Assess emotional expression and stage presence</li>
        <li><strong>Presentation (0-30):</strong> Consider overall presentation and audience engagement</li>
        <li><strong>Total Score:</strong> Maximum 100 points</li>
      </ul>
      <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.7;">Please be fair and objective in your evaluations.</p>
    </div>
  </section>
</div>

<footer>&copy; <?= date('Y') ?> Music Festival System</footer>

<script src="assets/js/scripts.js"></script>
</body>
</html>