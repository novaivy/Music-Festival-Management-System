<?php
session_start();
include('config.php');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$search = '';
$role_filter = '';

// Handle search and filter
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}
if (isset($_GET['role'])) {
    $role_filter = trim($_GET['role']);
}

// Build query
$query = "SELECT user_id, full_name, email, phone, city, country, role, is_active, last_login, date_created FROM users WHERE 1=1";

if (!empty($search)) {
    $search_escaped = "%" . $conn->real_escape_string($search) . "%";
    $query .= " AND (full_name LIKE '$search_escaped' OR email LIKE '$search_escaped' OR phone LIKE '$search_escaped')";
}

if (!empty($role_filter)) {
    $query .= " AND role = '" . $conn->real_escape_string($role_filter) . "'";
}

$query .= " ORDER BY date_created DESC";
$result = $conn->query($query);

// Handle user status toggle
if (isset($_GET['toggle_user']) && isset($_GET['user_id'])) {
    $uid = intval($_GET['user_id']);
    $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    if ($stmt->execute()) {
        header("Location: manage_users.php" . (!empty($search) ? "?search=$search" : "") . (!empty($role_filter) ? "&role=$role_filter" : ""));
        exit();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users - Admin Dashboard</title>
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

    .controls {
      background: white;
      padding: 1.5rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--dark-text);
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 0.75rem;
      border: 2px solid var(--border-color);
      border-radius: 6px;
      font-family: 'Poppins', sans-serif;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--secondary-color);
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
      align-self: flex-end;
    }

    .btn:hover {
      background: #1aa179;
      transform: translateY(-2px);
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
      white-space: nowrap;
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
      white-space: nowrap;
    }

    .badge-admin {
      background: #e7d4ff;
      color: #6f42c1;
    }

    .badge-judge {
      background: #d1ecf1;
      color: #0c5460;
    }

    .badge-participant {
      background: #d4edda;
      color: #155724;
    }

    .badge-active {
      background: #d4edda;
      color: #155724;
    }

    .badge-inactive {
      background: #f8d7da;
      color: #721c24;
    }

    .action-btns {
      display: flex;
      gap: 0.5rem;
    }

    .btn-sm {
      padding: 0.4rem 0.8rem;
      font-size: 0.85rem;
      text-decoration: none;
      display: inline-block;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    .btn-toggle {
      background: var(--primary-color);
      color: white;
    }

    .btn-toggle:hover {
      background: #5f3ba3;
    }

    .btn-view {
      background: var(--secondary-color);
      color: white;
    }

    .btn-view:hover {
      background: #1aa179;
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: #6c757d;
    }

    .last-login {
      font-size: 0.85rem;
      color: #6c757d;
    }

    @media (max-width: 768px) {
      table {
        font-size: 0.9rem;
      }

      th, td {
        padding: 0.8rem;
      }

      .action-btns {
        flex-direction: column;
      }

      .btn-sm {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>👥 Manage Users</h1>

    <div class="controls">
      <div class="form-group">
        <label>Search Users</label>
        <input type="text" id="searchInput" placeholder="Name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="form-group">
        <label>Filter by Role</label>
        <select id="roleFilter">
          <option value="">All Roles</option>
          <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
          <option value="judge" <?= $role_filter === 'judge' ? 'selected' : '' ?>>Judge</option>
          <option value="participant" <?= $role_filter === 'participant' ? 'selected' : '' ?>>Participant</option>
        </select>
      </div>
      <button class="btn" onclick="applyFilters()">Apply Filters</button>
      <a href="manage_users.php" class="btn" style="background: #6c757d;">Reset</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>City</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Login</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($user = $result->fetch_assoc()): ?>
              <tr>
                <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td><?= htmlspecialchars($user['city'] ?? 'N/A') ?></td>
                <td>
                  <span class="badge badge-<?= htmlspecialchars($user['role']) ?>">
                    <?= ucfirst($user['role']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge badge-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                    <?= $user['is_active'] ? '✓ Active' : '✗ Inactive' ?>
                  </span>
                </td>
                <td>
                  <div class="last-login">
                    <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?>
                  </div>
                </td>
                <td>
                  <div class="action-btns">
                    <a href="?toggle_user=1&user_id=<?= $user['user_id'] ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>" class="btn-sm btn-toggle">
                      <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="table-wrapper">
        <div class="empty-state">
          <div style="font-size: 2rem; margin-bottom: 1rem;">🔍</div>
          <p>No users found matching your criteria.</p>
        </div>
      </div>
    <?php endif; ?>

    <div style="margin-top: 2rem;">
      <a href="admin_dashboard.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">← Back to Dashboard</a>
    </div>
  </div>

  <script src="assets/js/scripts.js"></script>
  <script>
    function applyFilters() {
      const search = document.getElementById('searchInput').value;
      const role = document.getElementById('roleFilter').value;
      let url = 'manage_users.php';
      if (search) url += '?search=' + encodeURIComponent(search);
      if (role) url += (search ? '&' : '?') + 'role=' + encodeURIComponent(role);
      window.location.href = url;
    }

    document.getElementById('searchInput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') applyFilters();
    });
  </script>
</body>
</html>