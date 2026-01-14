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

// Handle adding new judge
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_judge'])) {
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $experience = intval($_POST['experience_years'] ?? 0);
    $bio = trim($_POST['bio'] ?? '');

    if (empty($email) || empty($full_name)) {
        $error = "Email and full name are required";
    } else {
        // Check for duplicate email
        $stmt = $conn->prepare("SELECT judge_id FROM judges WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Email already exists for a judge";
        } else {
            $stmt = $conn->prepare("INSERT INTO judges (full_name, email, phone, specialization, experience_years, bio, is_active) 
                                    VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssis", $full_name, $email, $phone, $specialization, $experience, $bio);
            if ($stmt->execute()) {
                $message = "Judge added successfully!";
            } else {
                $error = "Error adding judge: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Handle toggle judge status
if (isset($_GET['toggle_judge'])) {
    $jid = intval($_GET['judge_id']);
    $stmt = $conn->prepare("UPDATE judges SET is_active = NOT is_active WHERE judge_id = ?");
    $stmt->bind_param("i", $jid);
    if ($stmt->execute()) {
        header("Location: manage_judges.php");
        exit();
    }
    $stmt->close();
}

// Get all judges
$judges_result = $conn->query("SELECT judge_id, full_name, email, phone, specialization, experience_years, is_active FROM judges ORDER BY is_active DESC, date_added DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Judges - Admin Dashboard</title>
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
    .form-group textarea {
      padding: 0.75rem;
      border: 2px solid var(--border-color);
      border-radius: 6px;
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--secondary-color);
    }

    .form-group textarea {
      grid-column: 1 / -1;
      resize: vertical;
      min-height: 100px;
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
    }

    .btn:hover {
      background: #1aa179;
      transform: translateY(-2px);
    }

    .btn-submit {
      grid-column: 1 / -1;
      justify-self: start;
    }

    .table-wrapper {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      background: linear-gradient(135deg, #6f42c1 0%, #4c2a91 100%);
      color: white;
      padding: 1.2rem;
      text-align: left;
      font-weight: 600;
    }

    td {
      padding: 1.2rem;
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
    }

    .badge-active {
      background: #d4edda;
      color: #155724;
    }

    .badge-inactive {
      background: #f8d7da;
      color: #721c24;
    }

    .btn-sm {
      padding: 0.4rem 0.8rem;
      font-size: 0.85rem;
      text-decoration: none;
      display: inline-block;
      border-radius: 6px;
      color: white;
      transition: all 0.3s ease;
    }

    .btn-toggle {
      background: var(--primary-color);
    }

    .btn-toggle:hover {
      background: #5f3ba3;
    }

    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
      }

      table {
        font-size: 0.9rem;
      }

      th, td {
        padding: 0.8rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>⭐ Manage Judges</h1>

    <?php if ($message): ?>
      <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-card">
      <h2>➕ Add New Judge</h2>
      <form method="POST" class="form-grid">
        <div class="form-group">
          <label for="full_name">Full Name *</label>
          <input type="text" id="full_name" name="full_name" required>
        </div>

        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone">
        </div>

        <div class="form-group">
          <label for="specialization">Specialization</label>
          <input type="text" id="specialization" name="specialization" placeholder="e.g., Vocal, Instrumental, Dance">
        </div>

        <div class="form-group">
          <label for="experience_years">Years of Experience</label>
          <input type="number" id="experience_years" name="experience_years" min="0" value="0">
        </div>

        <div class="form-group">
          <label for="bio">Bio</label>
          <textarea id="bio" name="bio" placeholder="Brief biography or qualifications..."></textarea>
        </div>

        <button type="submit" name="add_judge" class="btn btn-submit">Add Judge</button>
      </form>
    </div>

    <h2>Judges List</h2>
    <?php if ($judges_result->num_rows > 0): ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Specialization</th>
              <th>Experience</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($judge = $judges_result->fetch_assoc()): ?>
              <tr>
                <td><strong><?= htmlspecialchars($judge['full_name']) ?></strong></td>
                <td><?= htmlspecialchars($judge['email']) ?></td>
                <td><?= htmlspecialchars($judge['phone']) ?></td>
                <td><?= htmlspecialchars($judge['specialization'] ?? 'N/A') ?></td>
                <td><?= intval($judge['experience_years']) ?> years</td>
                <td>
                  <span class="badge badge-<?= $judge['is_active'] ? 'active' : 'inactive' ?>">
                    <?= $judge['is_active'] ? '✓ Active' : '✗ Inactive' ?>
                  </span>
                </td>
                <td>
                  <a href="?toggle_judge=1&judge_id=<?= $judge['judge_id'] ?>" class="btn-sm btn-toggle">
                    <?= $judge['is_active'] ? 'Deactivate' : 'Activate' ?>
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="table-wrapper" style="padding: 2rem; text-align: center; color: #6c757d;">
        No judges added yet. Add one using the form above.
      </div>
    <?php endif; ?>

    <div style="margin-top: 2rem;">
      <a href="admin_dashboard.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">← Back to Dashboard</a>
    </div>
  </div>

  <script src="assets/js/scripts.js"></script>
</body>
</html>