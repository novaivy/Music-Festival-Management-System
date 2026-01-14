<?php
// index.php - simple homepage
require_once 'config.php';
require_once 'functions.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Music Festival Registration & Results Management System">
  <title>Music Festival Portal - Register, Compete & View Results</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header>
    <h1>🎵 Music Festival Registration & Results System</h1>
  </header>

  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
        <li><a href="<?php 
          echo ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : ( ($_SESSION['role'] == 'judge') ? 'judge_portal.php' : 'user_dashboard.php' ); 
        ?>">Dashboard</a></li>
        <li><a href="logout.php">Logout (<?= esc($_SESSION['full_name'] ?? 'User') ?>)</a></li>
      <?php else: ?>
        <li><a href="register.php">Register</a></li>
        <li><a href="login.php">Login</a></li>
      <?php endif; ?>
      <li><a href="results.php">View Results</a></li>
    </ul>
  </nav>

  <main>
    <div class="container">
      <section>
        <h2>Welcome to Our Music Festival!</h2>
        <p>Showcase your talent, compete with the best, and celebrate music with us. Whether you're a participant, judge, or administrator, we've made it easy to manage every aspect of the festival.</p>
        
        <div class="row mt-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">📝 For Participants</h3>
            </div>
            <div class="card-body">
              <p>Register for classes, submit your performances, and track your results. Connect with fellow musicians and showcase your talents on our platform.</p>
              <a href="<?= isset($_SESSION['user_id']) ? 'user_dashboard.php' : 'register.php' ?>" class="btn btn-primary">
                <?= isset($_SESSION['user_id']) ? 'Go to Dashboard' : 'Get Started' ?>
              </a>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title">⭐ For Judges</h3>
            </div>
            <div class="card-body">
              <p>Review performances, score participants, and provide constructive feedback. Access the judge portal to manage all scoring activities seamlessly.</p>
              <a href="<?= isset($_SESSION['user_id']) && $_SESSION['role'] == 'judge' ? 'judge_portal.php' : 'login.php' ?>" class="btn btn-info">
                <?= isset($_SESSION['user_id']) && $_SESSION['role'] == 'judge' ? 'Judge Portal' : 'Judge Login' ?>
              </a>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title">🔧 For Administrators</h3>
            </div>
            <div class="card-body">
              <p>Manage classes, approve registrations, oversee results, and ensure smooth festival operations with our admin dashboard.</p>
              <a href="<?= isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : 'login.php' ?>" class="btn btn-warning">
                <?= isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin' ? 'Admin Panel' : 'Admin Login' ?>
              </a>
            </div>
          </div>
        </div>

        <section class="mt-5">
          <h3>Quick Access</h3>
          <div class="row">
            <div class="card">
              <div class="card-body">
                <h4 class="text-primary">📊 View Results</h4>
                <p>Check the leaderboard and see who's winning across all categories.</p>
                <a href="results.php" class="btn btn-secondary btn-sm">Browse Results</a>
              </div>
            </div>

            <div class="card">
              <div class="card-body">
                <h4 class="text-primary">🎯 Classes</h4>
                <p>Explore the different music festival classes and competitions available.</p>
                <a href="results.php" class="btn btn-secondary btn-sm">See All Classes</a>
              </div>
            </div>

            <div class="card">
              <div class="card-body">
                <h4 class="text-primary">💬 Support</h4>
                <p>Have questions? Contact our support team for assistance.</p>
                <a href="mailto:info@musicfestival.com" class="btn btn-secondary btn-sm">Get Help</a>
              </div>
            </div>
          </div>
        </section>

        <section class="mt-5 bg-white p-4 text-center" style="border-top: 4px solid var(--secondary-color);">
          <h3>Why Register with Us?</h3>
          <div class="row mt-4">
            <div>
              <h5 class="text-primary">✅ Easy Registration</h5>
              <p>Quick and seamless registration process</p>
            </div>
            <div>
              <h5 class="text-primary">✅ Fair Judging</h5>
              <p>Expert judges evaluate all performances</p>
            </div>
            <div>
              <h5 class="text-primary">✅ Real-time Results</h5>
              <p>Check your scores instantly</p>
            </div>
            <div>
              <h5 class="text-primary">✅ Community</h5>
              <p>Connect with musicians worldwide</p>
            </div>
          </div>
        </section>
      </section>
    </div>
  </main>

  <footer>
    <p>&copy; <?php echo date('Y'); ?> Music Festival System. All Rights Reserved. | 
    <a href="index.php">Home</a> | 
    <a href="results.php">Results</a> | 
    <a href="login.php">Login</a></p>
  </footer>

  <script src="assets/js/scripts.js"></script>
</body>
</html>