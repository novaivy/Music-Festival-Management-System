<?php
session_start();
include('config.php');

// Check if user is logged in as participant
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle registration for a class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_class'])) {
    $class_id = intval($_POST['class_id'] ?? 0);
    $performance_title = trim($_POST['performance_title'] ?? '');
    $performance_description = trim($_POST['performance_description'] ?? '');
    $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
    $song_artist = trim($_POST['song_artist'] ?? '');
    $genre = trim($_POST['genre'] ?? '');

    if (empty($performance_title)) {
        $error = "Performance title is required";
    } elseif ($class_id <= 0) {
        $error = "Please select a class";
    } elseif ($duration_minutes <= 0 || $duration_minutes > 15) {
        $error = "Duration must be between 1 and 15 minutes";
    } else {
        // Check for duplicate registration
        $stmt = $conn->prepare("SELECT reg_id FROM registration WHERE user_id = ? AND class_id = ?");
        $stmt->bind_param("ii", $uid, $class_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "You are already registered for this class";
        } else {
            $stmt = $conn->prepare("INSERT INTO registration (user_id, class_id, performance_title, performance_description, duration_minutes, song_artist, genre, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("iisssss", $uid, $class_id, $performance_title, $performance_description, $duration_minutes, $song_artist, $genre);
            
            if ($stmt->execute()) {
                $message = "✅ You have successfully registered for the class! Your registration is pending approval.";
                // Reset form
                $_POST = array();
            } else {
                $error = "Error registering for class: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Get available classes
$classes_result = $conn->query("SELECT class_id, class_name, description, category, max_participants FROM classes WHERE is_active = 1 ORDER BY class_name ASC");

// Get user's registrations with class and scores
$registrations_result = $conn->query("
    SELECT r.reg_id, c.class_name, c.category, r.performance_title, r.status, r.reg_date, 
           res.score, res.position, res.rank
    FROM registration r
    JOIN classes c ON r.class_id = c.class_id
    LEFT JOIN results res ON r.reg_id = res.reg_id
    WHERE r.user_id = $uid
    ORDER BY r.reg_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Participant Dashboard - Music Festival</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: #f5f7fa;
    }

    header {
      background: linear-gradient(135deg, #20c997 0%, #13b489 100%);
      color: white;
      padding: 2rem;
    }

    header h1 {
      margin: 0;
      font-size: 2.5rem;
    }

    .user-info {
      opacity: 0.9;
      margin-top: 0.5rem;
      font-size: 0.95rem;
    }

    nav {
      background: rgba(255, 255, 255, 0.95);
      padding: 1rem 2rem;
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
      border-bottom: 2px solid #20c997;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    nav a {
      color: #20c997;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      padding: 0.5rem 1rem;
      border-radius: 6px;
    }

    nav a:hover {
      background: #20c997;
      color: white;
    }

    .container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
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

    h2 {
      color: var(--dark-text);
      border-bottom: 3px solid var(--secondary-color);
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
    }

    .form-card {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      margin-bottom: 2rem;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-group label {
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--dark-text);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      padding: 0.75rem;
      border: 2px solid var(--border-color);
      border-radius: 6px;
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--secondary-color);
    }

    .form-group textarea {
      grid-column: 1 / -1;
      resize: vertical;
      min-height: 80px;
    }

    .form-group.full {
      grid-column: 1 / -1;
    }

    .btn {
      padding: 0.75rem 1.5rem;
      background: var(--secondary-color);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      justify-self: start;
    }

    .btn:hover {
      background: #1aa179;
      transform: translateY(-2px);
    }

    .table-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      margin-bottom: 2rem;
    }

    .table-header {
      padding: 1.5rem;
      background: linear-gradient(135deg, #20c997 0%, #13b489 100%);
      color: white;
    }

    .table-header h3 {
      margin: 0;
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

    .badge {
      padding: 0.4rem 0.8rem;
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

    .badge-rejected {
      background: #f8d7da;
      color: #721c24;
    }

    .score-box {
      background: linear-gradient(135deg, #20c997 0%, #13b489 100%);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      font-weight: 600;
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: #6c757d;
    }

    .empty-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
      header h1 {
        font-size: 1.8rem;
      }

      nav {
        flex-direction: column;
        gap: 0.5rem;
      }

      .form-grid {
        grid-template-columns: 1fr;
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
  <header>
    <h1>🎵 Participant Dashboard</h1>
    <div class="user-info">
      Welcome, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong> (<?= htmlspecialchars($_SESSION['email']) ?>)
    </div>
  </header>

  <nav>
    <a href="user_dashboard.php">📊 My Dashboard</a>
    <a href="results.php">🏆 View Results</a>
    <a href="logout.php">🚪 Logout</a>
  </nav>

  <div class="container">
    <?php if ($message): ?>
      <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-card">
      <h2>📝 Register for a Class</h2>
      <form method="POST" class="form-grid">
        <div class="form-group">
          <label for="class_id">Select Class *</label>
          <select id="class_id" name="class_id" required>
            <option value="">-- Choose a class --</option>
            <?php while ($class = $classes_result->fetch_assoc()): ?>
              <option value="<?= $class['class_id'] ?>">
                <?= htmlspecialchars($class['class_name']) ?> (<?= htmlspecialchars($class['category']) ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="performance_title">Performance Title *</label>
          <input type="text" id="performance_title" name="performance_title" placeholder="e.g., 'Amazing Grace'" value="<?= htmlspecialchars($_POST['performance_title'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="song_artist">Song/Artist</label>
          <input type="text" id="song_artist" name="song_artist" placeholder="Original artist or composer" value="<?= htmlspecialchars($_POST['song_artist'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="genre">Genre</label>
          <input type="text" id="genre" name="genre" placeholder="e.g., Gospel, Classical, Jazz" value="<?= htmlspecialchars($_POST['genre'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="duration_minutes">Duration (minutes) *</label>
          <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="15" placeholder="1-15 minutes" value="<?= htmlspecialchars($_POST['duration_minutes'] ?? '') ?>" required>
        </div>

        <div class="form-group full">
          <label for="performance_description">Performance Description</label>
          <textarea id="performance_description" name="performance_description" placeholder="Describe your performance..."></textarea>
        </div>

        <button type="submit" name="register_class" class="btn">Register for Class</button>
      </form>
    </div>

    <h2>📋 Your Registrations</h2>
    <?php if ($registrations_result->num_rows > 0): ?>
      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th>Class</th>
              <th>Performance</th>
              <th>Category</th>
              <th>Status</th>
              <th>Score</th>
              <th>Position</th>
              <th>Registered</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($reg = $registrations_result->fetch_assoc()): ?>
              <tr>
                <td><strong><?= htmlspecialchars($reg['class_name']) ?></strong></td>
                <td><?= htmlspecialchars($reg['performance_title']) ?></td>
                <td><?= htmlspecialchars($reg['category']) ?></td>
                <td>
                  <span class="badge badge-<?= strtolower($reg['status']) ?>">
                    <?= htmlspecialchars($reg['status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($reg['score']): ?>
                    <span class="score-box"><?= number_format($reg['score'], 2) ?>/100</span>
                  <?php else: ?>
                    <span style="color: #6c757d;">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($reg['position']): ?>
                    <strong><?= htmlspecialchars($reg['position']) ?></strong>
                  <?php else: ?>
                    <span style="color: #6c757d;">-</span>
                  <?php endif; ?>
                </td>
                <td><?= date('M d, Y', strtotime($reg['reg_date'])) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="table-card">
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <p>You haven't registered for any classes yet.</p>
          <p style="color: #999;">Use the form above to register for your first class!</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <footer style="text-align: center; padding: 2rem; color: #6c757d; border-top: 1px solid #e9ecef; margin-top: 3rem;">
    &copy; <?= date('Y') ?> Music Festival Management System
  </footer>

  <script src="assets/js/scripts.js"></script>
</body>
</html>