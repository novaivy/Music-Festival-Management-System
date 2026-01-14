<?php
session_start();
include('config.php');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get statistics from database
$stats = array();

// Total Users
$result = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'participant'");
$stats['total_users'] = $result->fetch_assoc()['cnt'];

// Total Classes
$result = $conn->query("SELECT COUNT(*) AS cnt FROM classes WHERE is_active = 1");
$stats['total_classes'] = $result->fetch_assoc()['cnt'];

// Total Registrations
$result = $conn->query("SELECT COUNT(*) AS cnt FROM registration");
$stats['total_registrations'] = $result->fetch_assoc()['cnt'];

// Pending Registrations
$result = $conn->query("SELECT COUNT(*) AS cnt FROM registration WHERE status = 'Pending'");
$stats['pending_registrations'] = $result->fetch_assoc()['cnt'];

// Total Results Submitted
$result = $conn->query("SELECT COUNT(*) AS cnt FROM results");
$stats['total_results'] = $result->fetch_assoc()['cnt'];

// Active Judges
$result = $conn->query("SELECT COUNT(*) AS cnt FROM judges WHERE is_active = 1");
$stats['active_judges'] = $result->fetch_assoc()['cnt'];

// Recent Registrations
$recent_regs = $conn->query("
    SELECT r.reg_id, u.full_name, c.class_name, r.status, r.reg_date 
    FROM registration r
    JOIN users u ON r.user_id = u.user_id
    JOIN classes c ON r.class_id = c.class_id
    ORDER BY r.reg_date DESC 
    LIMIT 5
");

// Pending Approvals
$pending_approvals = $conn->query("
    SELECT r.reg_id, u.full_name, c.class_name, r.performance_title, r.reg_date 
    FROM registration r
    JOIN users u ON r.user_id = u.user_id
    JOIN classes c ON r.class_id = c.class_id
    WHERE r.status = 'Pending'
    ORDER BY r.reg_date ASC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Music Festival</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: #f5f7fa;
    }

    header {
      background: linear-gradient(135deg, #6f42c1 0%, #4c2a91 100%);
      color: white;
      padding: 2rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    header h1 {
      margin: 0;
      font-size: 2.5rem;
    }

    .admin-nav {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 2px solid rgba(255, 255, 255, 0.2);
    }

    .admin-nav a {
      color: white;
      padding: 0.75rem 1rem;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 6px;
      text-decoration: none;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 500;
    }

    .admin-nav a:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateX(2px);
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .stat-card {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      border-left: 5px solid var(--primary-color);
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    }

    .stat-card.users {
      border-left-color: #007bff;
    }

    .stat-card.classes {
      border-left-color: #20c997;
    }

    .stat-card.registrations {
      border-left-color: #fd7e14;
    }

    .stat-card.pending {
      border-left-color: #dc3545;
    }

    .stat-card.results {
      border-left-color: #6f42c1;
    }

    .stat-card.judges {
      border-left-color: #17a2b8;
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin: 0.5rem 0;
    }

    .stat-label {
      color: #6c757d;
      font-size: 0.95rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .section {
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .section h2 {
      margin-top: 0;
      color: var(--dark-text);
      border-bottom: 3px solid var(--secondary-color);
      padding-bottom: 1rem;
    }

    .recent-list {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .list-item {
      padding: 1.5rem;
      border-bottom: 1px solid #e9ecef;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background 0.3s ease;
    }

    .list-item:last-child {
      border-bottom: none;
    }

    .list-item:hover {
      background-color: #f8f9fa;
    }

    .item-info {
      flex: 1;
    }

    .item-title {
      font-weight: 600;
      color: var(--dark-text);
      margin-bottom: 0.3rem;
    }

    .item-detail {
      font-size: 0.9rem;
      color: #6c757d;
    }

    .badge {
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .badge-pending {
      background: #fff3cd;
      color: #856404;
    }

    .badge-approved {
      background: #d4edda;
      color: #155724;
    }

    .btn-view {
      background: var(--primary-color);
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.3s ease;
      display: inline-block;
    }

    .btn-view:hover {
      background: #5f3ba3;
      transform: translateY(-2px);
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: #6c757d;
    }

    .empty-state-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
    }

    .action-btn {
      background: white;
      padding: 1.5rem;
      border-radius: 12px;
      text-align: center;
      text-decoration: none;
      color: var(--dark-text);
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .action-btn:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
      color: var(--primary-color);
    }

    .action-icon {
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
    }

    @media (max-width: 768px) {
      .dashboard-grid {
        grid-template-columns: 1fr;
      }

      .section {
        padding: 1rem;
      }
    }
  </style>
</head>
<body>
  <header>
    <h1>🎵 Admin Dashboard</h1>
    <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
    <nav class="admin-nav">
      <a href="manage_classes.php">📚 Manage Classes</a>
      <a href="manage_users.php">👥 Manage Users</a>
      <a href="manage_judges.php">⭐ Manage Judges</a>
      <a href="approve_registrations.php">✅ Approve Registrations</a>
      <a href="reports_analytics.php">📊 Reports & Analytics</a>
      <a href="view_registerations.php">📋 View Registrations</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </header>

  <div class="dashboard-grid">
    <div class="stat-card users">
      <div class="stat-label">Total Participants</div>
      <div class="stat-number"><?= $stats['total_users'] ?></div>
      <a href="manage_users.php" style="color: #007bff; text-decoration: none; font-size: 0.9rem;">Manage users →</a>
    </div>

    <div class="stat-card classes">
      <div class="stat-label">Active Classes</div>
      <div class="stat-number"><?= $stats['total_classes'] ?></div>
      <a href="manage_classes.php" style="color: #20c997; text-decoration: none; font-size: 0.9rem;">Manage classes →</a>
    </div>

    <div class="stat-card registrations">
      <div class="stat-label">Total Registrations</div>
      <div class="stat-number"><?= $stats['total_registrations'] ?></div>
      <a href="view_registerations.php" style="color: #fd7e14; text-decoration: none; font-size: 0.9rem;">View all →</a>
    </div>

    <div class="stat-card pending">
      <div class="stat-label">Pending Approval</div>
      <div class="stat-number" style="color: #dc3545;"><?= $stats['pending_registrations'] ?></div>
      <a href="approve_registrations.php" style="color: #dc3545; text-decoration: none; font-size: 0.9rem;">Review pending →</a>
    </div>

    <div class="stat-card results">
      <div class="stat-label">Results Submitted</div>
      <div class="stat-number"><?= $stats['total_results'] ?></div>
      <a href="results.php" style="color: #6f42c1; text-decoration: none; font-size: 0.9rem;">View results →</a>
    </div>

    <div class="stat-card judges">
      <div class="stat-label">Active Judges</div>
      <div class="stat-number"><?= $stats['active_judges'] ?></div>
      <a href="manage_judges.php" style="color: #17a2b8; text-decoration: none; font-size: 0.9rem;">Manage judges →</a>
    </div>
  </div>

  <div class="section">
    <h2>⚡ Quick Actions</h2>
    <div class="quick-actions">
      <a href="manage_classes.php" class="action-btn">
        <div class="action-icon">➕</div>
        <div>Add New Class</div>
      </a>
      <a href="manage_users.php" class="action-btn">
        <div class="action-icon">👤</div>
        <div>Manage Users</div>
      </a>
      <a href="approve_registrations.php" class="action-btn">
        <div class="action-icon">✔️</div>
        <div>Review Registrations</div>
      </a>
      <a href="upload_result.php" class="action-btn">
        <div class="action-icon">⭐</div>
        <div>Upload Scores</div>
      </a>
    </div>
  </div>

  <div class="section">
    <h2>📋 Recent Registrations</h2>
    <?php if ($recent_regs->num_rows > 0): ?>
      <div class="recent-list">
        <?php while($reg = $recent_regs->fetch_assoc()): ?>
          <div class="list-item">
            <div class="item-info">
              <div class="item-title"><?= htmlspecialchars($reg['full_name']) ?></div>
              <div class="item-detail"><?= htmlspecialchars($reg['class_name']) ?> • <?= date('M d, Y', strtotime($reg['reg_date'])) ?></div>
            </div>
            <span class="badge <?= $reg['status'] === 'Pending' ? 'badge-pending' : 'badge-approved' ?>">
              <?= htmlspecialchars($reg['status']) ?>
            </span>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-state-icon">📭</div>
        <p>No registrations yet</p>
      </div>
    <?php endif; ?>
  </div>

  <div class="section">
    <h2>⏳ Pending Approvals</h2>
    <?php if ($pending_approvals->num_rows > 0): ?>
      <div class="recent-list">
        <?php while($pending = $pending_approvals->fetch_assoc()): ?>
          <div class="list-item">
            <div class="item-info">
              <div class="item-title"><?= htmlspecialchars($pending['full_name']) ?> - <?= htmlspecialchars($pending['performance_title']) ?></div>
              <div class="item-detail"><?= htmlspecialchars($pending['class_name']) ?> • Registered: <?= date('M d, Y', strtotime($pending['reg_date'])) ?></div>
            </div>
            <a href="approve_registrations.php?reg_id=<?= $pending['reg_id'] ?>" class="btn-view">Review</a>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-state-icon">✅</div>
        <p>All registrations approved!</p>
      </div>
    <?php endif; ?>
  </div>

  <footer style="text-align: center; padding: 2rem; color: #6c757d; border-top: 1px solid #e9ecef; margin-top: 3rem;">
    &copy; <?= date('Y') ?> Music Festival Management System. All rights reserved.
  </footer>

  <script src="assets/js/scripts.js"></script>
</body>
</html>