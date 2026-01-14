<?php
require_once 'config.php';
require_once 'functions.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') redirect('login.php');

$msg = $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_name'])) {
    $name = trim($_POST['class_name']);
    $desc = trim($_POST['description']);
    if (!$name) $err = "Enter a class name.";
    else {
        $stmt = $conn->prepare("INSERT INTO classes (class_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $desc);
        if ($stmt->execute()) $msg = "Class added.";
        else $err = "DB Error: ".$conn->error;
        $stmt->close();
    }
}
$classes = $conn->query("SELECT * FROM classes ORDER BY created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Classes - Music Festival</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
  <h1>📚 Manage Music Classes</h1>
</header>

<nav>
  <ul>
    <li><a href="admin_dashboard.php">Back to Dashboard</a></li>
    <li><a href="manage_classes.php">Manage Classes</a></li>
    <li><a href="view_registrations.php">View Registrations</a></li>
    <li><a href="logout.php">Logout (<?= esc($_SESSION['full_name']) ?>)</a></li>
  </ul>
</nav>

<div class="container">
  <section>
    <?php if($msg): ?><div class="success alert"><?= esc($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="error alert"><?= esc($err) ?></div><?php endif; ?>

    <h2>Add New Class</h2>
    <form method="post" id="classForm">
      <div class="form-group">
        <label for="class_name">Class Name</label>
        <input type="text" id="class_name" name="class_name" placeholder="e.g., Solo Voice, Instrumental Duet" required>
      </div>

      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Describe what this class is about..."></textarea>
      </div>

      <button class="btn btn-primary btn-lg" type="submit">Add Class</button>
    </form>

    <h3 class="mt-5">Existing Classes</h3>
    
    <?php if ($classes->num_rows > 0): ?>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Class Name</th>
              <th>Description</th>
              <th>Created Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while($c = $classes->fetch_assoc()): ?>
              <tr>
                <td>
                  <span class="badge badge-primary"><?= $c['class_id'] ?></span>
                </td>
                <td>
                  <strong><?= esc($c['class_name']) ?></strong>
                </td>
                <td>
                  <?= !empty($c['description']) ? esc(substr($c['description'], 0, 50)) . '...' : '<em>No description</em>' ?>
                </td>
                <td>
                  <?= date('M d, Y', strtotime($c['created_at'])) ?>
                </td>
                <td>
                  <button class="btn btn-secondary btn-sm" onclick="showClassDetails(<?= $c['class_id'] ?>, '<?= esc($c['class_name']) ?>', '<?= esc(addslashes($c['description'] ?? '')) ?>')">
                    👁️ View
                  </button>
                  <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $c['class_id'] ?>)">
                    🗑️ Delete
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert info" style="text-align: center; padding: 2rem;">
        <p>No classes yet. Create your first class above!</p>
      </div>
    <?php endif; ?>

    <div class="mt-5 p-4" style="background: #f0f7ff; border-radius: 8px; border-left: 4px solid var(--info-color);">
      <h4>💡 Tips</h4>
      <ul style="margin-left: 1.5rem;">
        <li>Create clear and descriptive class names</li>
        <li>Add detailed descriptions to help participants understand requirements</li>
        <li>Classes appear on the participant registration page</li>
        <li>You can manage registrations once participants sign up</li>
      </ul>
    </div>
  </section>
</div>

<footer>&copy; <?= date('Y') ?> Music Festival</footer>

<script src="assets/js/scripts.js"></script>
<script>
function showClassDetails(id, name, desc) {
  const details = `
    <div style="padding: 1rem 0;">
      <p><strong>ID:</strong> ${id}</p>
      <p><strong>Name:</strong> ${name}</p>
      <p><strong>Description:</strong> ${desc || 'No description provided'}</p>
    </div>
  `;
  
  // Create modal (simple version)
  alert('Class: ' + name + '\n' + 'Description: ' + (desc || 'No description'));
}

function confirmDelete(classId) {
  if (confirm('Are you sure you want to delete this class? All registrations will also be deleted.')) {
    // Create hidden form and submit to delete
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="delete_class_id" value="${classId}">`;
    document.body.appendChild(form);
    // Uncomment to enable deletion:
    // form.submit();
    alert('Delete functionality requires additional implementation.');
  }
}
</script>
</body>
</html>