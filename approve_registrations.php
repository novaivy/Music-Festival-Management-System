<?php
session_start();
include('config.php');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$reg_id = isset($_GET['reg_id']) ? intval($_GET['reg_id']) : null;

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rid = intval($_POST['reg_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');

    if ($rid > 0 && in_array($action, ['Approved', 'Rejected'])) {
        $stmt = $conn->prepare("UPDATE registration SET status = ?, rejection_reason = ? WHERE reg_id = ?");
        $stmt->bind_param("ssi", $action, $rejection_reason, $rid);
        
        if ($stmt->execute()) {
            $message = "Registration $action successfully!";
            $reg_id = null;
        } else {
            $error = "Error updating registration: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get pending registrations
$query = "SELECT r.reg_id, u.user_id, u.full_name, u.email, u.phone, u.city, c.class_name, r.performance_title, 
                r.performance_description, r.duration_minutes, r.song_artist, r.genre, r.reg_date, r.status, r.notes
          FROM registration r
          JOIN users u ON r.user_id = u.user_id
          JOIN classes c ON r.class_id = c.class_id
          WHERE r.status = 'Pending'
          ORDER BY r.reg_date ASC";

$pending_result = $conn->query($query);

// Get details for current registration if viewing specific one
$current_reg = null;
if ($reg_id) {
    $stmt = $conn->prepare("SELECT r.reg_id, u.full_name, u.email, u.phone, u.city, u.country, c.class_name, 
                                   r.performance_title, r.performance_description, r.duration_minutes, r.song_artist, 
                                   r.genre, r.reg_date, r.status, r.rejection_reason, r.notes
                            FROM registration r
                            JOIN users u ON r.user_id = u.user_id
                            JOIN classes c ON r.class_id = c.class_id
                            WHERE r.reg_id = ?");
    $stmt->bind_param("i", $reg_id);
    $stmt->execute();
    $current_reg = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approve Registrations - Admin Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: #f5f7fa;
    }

    .container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    h1 {
      color: var(--primary-color);
      border-bottom: 3px solid var(--secondary-color);
      padding-bottom: 1rem;
      margin-bottom: 2rem;
    }

    .alert {
      padding: 1rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
    }

    .alert.success {
      background: #d4edda;
      color: #155724;
      border-left: 4px solid #28a745;
    }

    .alert.error {
      background: #f8d7da;
      color: #721c24;
      border-left: 4px solid #dc3545;
    }

    .details-card {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      margin-bottom: 2rem;
    }

    .detail-row {
      display: grid;
      grid-template-columns: 150px 1fr;
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #e9ecef;
    }

    .detail-label {
      font-weight: 600;
      color: var(--dark-text);
    }

    .detail-value {
      color: #6c757d;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--dark-text);
    }

    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 2px solid var(--border-color);
      border-radius: 6px;
      font-family: 'Poppins', sans-serif;
      resize: vertical;
      min-height: 100px;
    }

    .form-group textarea:focus {
      outline: none;
      border-color: var(--secondary-color);
    }

    .button-group {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
    }

    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      color: white;
    }

    .btn-approve {
      background: #28a745;
    }

    .btn-approve:hover {
      background: #218838;
      transform: translateY(-2px);
    }

    .btn-reject {
      background: #dc3545;
    }

    .btn-reject:hover {
      background: #c82333;
      transform: translateY(-2px);
    }

    .btn-cancel {
      background: #6c757d;
    }

    .btn-cancel:hover {
      background: #5a6268;
    }

    .pending-list {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .pending-item {
      padding: 1.5rem;
      border-bottom: 1px solid #e9ecef;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background 0.3s ease;
      cursor: pointer;
    }

    .pending-item:hover {
      background-color: #f8f9fa;
    }

    .pending-info {
      flex: 1;
    }

    .pending-name {
      font-weight: 600;
      color: var(--dark-text);
      margin-bottom: 0.3rem;
    }

    .pending-detail {
      font-size: 0.9rem;
      color: #6c757d;
    }

    .badge {
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      background: #fff3cd;
      color: #856404;
    }

    .btn-view {
      background: var(--primary-color);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s ease;
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

    @media (max-width: 768px) {
      .detail-row {
        grid-template-columns: 1fr;
      }

      .button-group {
        flex-direction: column;
      }

      .btn {
        width: 100%;
      }

      .pending-item {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>✅ Approve Registrations</h1>

    <?php if ($message): ?>
      <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($current_reg): ?>
      <div class="details-card">
        <h2>Registration Details</h2>

        <div class="detail-row">
          <div class="detail-label">Participant</div>
          <div class="detail-value"><?= htmlspecialchars($current_reg['full_name']) ?></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Email</div>
          <div class="detail-value"><?= htmlspecialchars($current_reg['email']) ?></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Phone</div>
          <div class="detail-value"><?= htmlspecialchars($current_reg['phone']) ?></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Location</div>
          <div class="detail-value"><?= htmlspecialchars($current_reg['city'] . ', ' . $current_reg['country']) ?></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Class</div>
          <div class="detail-value"><strong><?= htmlspecialchars($current_reg['class_name']) ?></strong></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Performance</div>
          <div class="detail-value"><?= htmlspecialchars($current_reg['performance_title']) ?></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Artist/Song</div>
          <div class="detail-value"><?= htmlspecialchars($current_reg['song_artist'] ?? 'N/A') ?> - <?= htmlspecialchars($current_reg['genre'] ?? 'N/A') ?></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Duration</div>
          <div class="detail-value"><?= intval($current_reg['duration_minutes']) ?> minutes</div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Description</div>
          <div class="detail-value"><?= htmlspecialchars($current_reg['performance_description'] ?? 'N/A') ?></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Registered</div>
          <div class="detail-value"><?= date('M d, Y H:i', strtotime($current_reg['reg_date'])) ?></div>
        </div>

        <form method="POST" style="margin-top: 2rem;">
          <input type="hidden" name="reg_id" value="<?= $current_reg['reg_id'] ?>">

          <div class="form-group" style="grid-column: 1 / -1;">
            <label for="rejection_reason">Rejection Reason (if rejecting)</label>
            <textarea id="rejection_reason" name="rejection_reason" placeholder="Provide reason if rejecting this registration..."></textarea>
          </div>

          <div class="button-group">
            <button type="submit" name="action" value="Approved" class="btn btn-approve">✓ Approve Registration</button>
            <button type="submit" name="action" value="Rejected" class="btn btn-reject">✗ Reject Registration</button>
            <a href="approve_registrations.php" class="btn btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
    <?php else: ?>
      <h2>Pending Registrations</h2>
      <?php if ($pending_result->num_rows > 0): ?>
        <div class="pending-list">
          <?php while ($pending = $pending_result->fetch_assoc()): ?>
            <div class="pending-item">
              <div class="pending-info">
                <div class="pending-name"><?= htmlspecialchars($pending['full_name']) ?></div>
                <div class="pending-detail">
                  <strong><?= htmlspecialchars($pending['class_name']) ?></strong> - 
                  <?= htmlspecialchars($pending['performance_title']) ?> • 
                  Registered: <?= date('M d, Y', strtotime($pending['reg_date'])) ?>
                </div>
              </div>
              <span class="badge">Pending</span>
              <a href="?reg_id=<?= $pending['reg_id'] ?>" class="btn-view" style="margin-left: 1rem;">Review</a>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="pending-list">
          <div class="empty-state">
            <div style="font-size: 2rem; margin-bottom: 1rem;">✅</div>
            <p>No pending registrations! All registrations have been approved or rejected.</p>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div style="margin-top: 2rem;">
      <a href="admin_dashboard.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">← Back to Dashboard</a>
    </div>
  </div>

  <script src="assets/js/scripts.js"></script>
</body>
</html>