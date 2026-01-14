<?php
session_start();
include('config.php');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get analytics data
$total_registrations = $conn->query("SELECT COUNT(*) AS cnt FROM registration")->fetch_assoc()['cnt'];
$approved_registrations = $conn->query("SELECT COUNT(*) AS cnt FROM registration WHERE status = 'Approved'")->fetch_assoc()['cnt'];
$rejected_registrations = $conn->query("SELECT COUNT(*) AS cnt FROM registration WHERE status = 'Rejected'")->fetch_assoc()['cnt'];
$pending_registrations = $conn->query("SELECT COUNT(*) AS cnt FROM registration WHERE status = 'Pending'")->fetch_assoc()['cnt'];

// Class-wise registration distribution
$class_stats = $conn->query("
    SELECT c.class_name, COUNT(r.reg_id) AS total_regs, 
           SUM(CASE WHEN r.status = 'Approved' THEN 1 ELSE 0 END) AS approved
    FROM classes c
    LEFT JOIN registration r ON c.class_id = r.class_id
    GROUP BY c.class_id, c.class_name
    ORDER BY total_regs DESC
");

// Judge scores distribution
$judge_scores = $conn->query("
    SELECT j.full_name, COUNT(r.result_id) AS total_scores, AVG(r.score) AS avg_score
    FROM judges j
    LEFT JOIN results r ON j.judge_id = r.judge_id
    GROUP BY j.judge_id, j.full_name
    ORDER BY total_scores DESC
");

// Top performers
$top_performers = $conn->query("
    SELECT u.full_name, c.class_name, r.score, r.position
    FROM results r
    JOIN registration reg ON r.reg_id = reg.reg_id
    JOIN users u ON reg.user_id = u.user_id
    JOIN classes c ON reg.class_id = c.class_id
    WHERE r.score IS NOT NULL
    ORDER BY r.score DESC
    LIMIT 10
");

// Registration trend (by month)
$monthly_trend = $conn->query("
    SELECT DATE_FORMAT(reg_date, '%Y-%m') AS month, COUNT(*) AS count
    FROM registration
    GROUP BY DATE_FORMAT(reg_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports & Analytics - Admin Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: #f5f7fa;
    }

    .container {
      max-width: 1400px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    h1 {
      color: var(--primary-color);
      border-bottom: 3px solid var(--secondary-color);
      padding-bottom: 1rem;
      margin-bottom: 2rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: white;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      border-left: 5px solid var(--primary-color);
    }

    .stat-card.total {
      border-left-color: #007bff;
    }

    .stat-card.approved {
      border-left-color: #28a745;
    }

    .stat-card.rejected {
      border-left-color: #dc3545;
    }

    .stat-card.pending {
      border-left-color: #ffc107;
    }

    .stat-label {
      font-size: 0.9rem;
      color: #6c757d;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.5rem;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      color: var(--dark-text);
    }

    .percentage {
      font-size: 0.85rem;
      color: #6c757d;
      margin-top: 0.5rem;
    }

    .table-card {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      margin-bottom: 2rem;
    }

    .table-card h2 {
      margin-top: 0;
      color: var(--dark-text);
      border-bottom: 2px solid var(--secondary-color);
      padding-bottom: 1rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      background: #f8f9fa;
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      border-bottom: 2px solid #e9ecef;
      color: var(--dark-text);
    }

    td {
      padding: 1rem;
      border-bottom: 1px solid #e9ecef;
    }

    tr:hover {
      background-color: #f8f9fa;
    }

    .progress-bar {
      width: 100%;
      height: 8px;
      background: #e9ecef;
      border-radius: 4px;
      overflow: hidden;
      margin-top: 0.5rem;
    }

    .progress-fill {
      height: 100%;
      background: var(--secondary-color);
      transition: width 0.3s ease;
    }

    .export-btn {
      background: var(--primary-color);
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      margin-bottom: 1rem;
    }

    .export-btn:hover {
      background: #5f3ba3;
      transform: translateY(-2px);
    }

    .badge {
      padding: 0.3rem 0.7rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .badge-gold {
      background: #fff8dc;
      color: #b8860b;
    }

    .badge-silver {
      background: #f5f5f5;
      color: #696969;
    }

    .badge-bronze {
      background: #ffe4b5;
      color: #cd7f32;
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: #6c757d;
    }

    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .table-card {
        padding: 1rem;
      }

      table {
        font-size: 0.9rem;
      }

      th, td {
        padding: 0.7rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>📊 Reports & Analytics</h1>

    <div class="stats-grid">
      <div class="stat-card total">
        <div class="stat-label">Total Registrations</div>
        <div class="stat-number"><?= $total_registrations ?></div>
      </div>

      <div class="stat-card approved">
        <div class="stat-label">Approved</div>
        <div class="stat-number"><?= $approved_registrations ?></div>
        <div class="percentage"><?= $total_registrations > 0 ? round(($approved_registrations / $total_registrations) * 100) : 0 ?>%</div>
      </div>

      <div class="stat-card rejected">
        <div class="stat-label">Rejected</div>
        <div class="stat-number"><?= $rejected_registrations ?></div>
        <div class="percentage"><?= $total_registrations > 0 ? round(($rejected_registrations / $total_registrations) * 100) : 0 ?>%</div>
      </div>

      <div class="stat-card pending">
        <div class="stat-label">Pending</div>
        <div class="stat-number"><?= $pending_registrations ?></div>
        <div class="percentage"><?= $total_registrations > 0 ? round(($pending_registrations / $total_registrations) * 100) : 0 ?>%</div>
      </div>
    </div>

    <div class="table-card">
      <h2>Class-wise Registration Distribution</h2>
      <button class="export-btn" onclick="exportTableToCSV('classTable', 'class_distribution.csv')">📥 Export to CSV</button>
      
      <?php if ($class_stats->num_rows > 0): ?>
        <table id="classTable">
          <thead>
            <tr>
              <th>Class Name</th>
              <th>Total Registrations</th>
              <th>Approved</th>
              <th>Progress</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($class = $class_stats->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($class['class_name']) ?></td>
                <td><?= intval($class['total_regs']) ?></td>
                <td><?= intval($class['approved'] ?? 0) ?></td>
                <td>
                  <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $class['total_regs'] > 0 ? (($class['approved'] / $class['total_regs']) * 100) : 0 ?>%"></div>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">No class data available yet.</div>
      <?php endif; ?>
    </div>

    <div class="table-card">
      <h2>Judge Scoring Activity</h2>
      <button class="export-btn" onclick="exportTableToCSV('judgeTable', 'judge_activity.csv')">📥 Export to CSV</button>
      
      <?php if ($judge_scores->num_rows > 0): ?>
        <table id="judgeTable">
          <thead>
            <tr>
              <th>Judge Name</th>
              <th>Total Scores Submitted</th>
              <th>Average Score</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($judge = $judge_scores->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($judge['full_name']) ?></td>
                <td><?= intval($judge['total_scores'] ?? 0) ?></td>
                <td><?= $judge['avg_score'] ? number_format($judge['avg_score'], 2) : 'N/A' ?>/100</td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">No judge scoring data available yet.</div>
      <?php endif; ?>
    </div>

    <div class="table-card">
      <h2>🏆 Top 10 Performers</h2>
      <button class="export-btn" onclick="exportTableToCSV('performersTable', 'top_performers.csv')">📥 Export to CSV</button>
      
      <?php if ($top_performers->num_rows > 0): ?>
        <table id="performersTable">
          <thead>
            <tr>
              <th>Rank</th>
              <th>Participant</th>
              <th>Class</th>
              <th>Score</th>
              <th>Position</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $rank = 1;
            while ($perf = $top_performers->fetch_assoc()): 
              $badge_class = $rank == 1 ? 'gold' : ($rank == 2 ? 'silver' : ($rank == 3 ? 'bronze' : ''));
            ?>
              <tr>
                <td>
                  <?php if ($badge_class): ?>
                    <span class="badge badge-<?= $badge_class ?>">
                      <?= $rank == 1 ? '🥇 1st' : ($rank == 2 ? '🥈 2nd' : '🥉 3rd') ?>
                    </span>
                  <?php else: ?>
                    <?= $rank ?>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($perf['full_name']) ?></td>
                <td><?= htmlspecialchars($perf['class_name']) ?></td>
                <td><strong><?= number_format($perf['score'], 2) ?>/100</strong></td>
                <td><?= htmlspecialchars($perf['position'] ?? 'N/A') ?></td>
              </tr>
              <?php $rank++; ?>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">No performance scores available yet.</div>
      <?php endif; ?>
    </div>

    <div style="margin-top: 2rem;">
      <a href="admin_dashboard.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">← Back to Dashboard</a>
    </div>
  </div>

  <script src="assets/js/scripts.js"></script>
</body>
</html>