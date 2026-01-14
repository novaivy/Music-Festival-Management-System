<?php
session_start();
include('functions.php');
include('config.php');

$error = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Email and password are required";
    } else {
        // Use prepared statement for security
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role, phone, city, is_active FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Check if user is active
            if (!$user['is_active']) {
                $error = "Your account has been deactivated. Please contact support.";
            } elseif (password_verify($password, $user['password'])) {
                // Set comprehensive session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['city'] = $user['city'];
                $_SESSION['login_time'] = date('Y-m-d H:i:s');

                // Update last_login timestamp
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("i", $user['user_id']);
                $update_stmt->execute();
                $update_stmt->close();

                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['role'] == 'judge') {
                    header("Location: judge_portal.php");
                } else {
                    header("Location: user_dashboard.php");
                }
                exit();
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that email. Please check or register.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Music Festival</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, var(--primary-color) 0%, #8b5cf6 100%);
      padding: 2rem;
    }

    .login-container {
      background: white;
      padding: 3rem;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 400px;
      animation: slideDown 0.5s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-container h2 {
      text-align: center;
      margin-bottom: 2rem;
      color: var(--primary-color);
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .login-container label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--dark-text);
    }

    .login-container input {
      width: 100%;
      padding: 0.85rem;
      border: 2px solid var(--border-color);
      border-radius: 6px;
      font-size: 1rem;
      font-family: 'Poppins', sans-serif;
      transition: var(--transition);
    }

    .login-container input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1);
    }

    .login-container button {
      width: 100%;
      padding: 0.85rem;
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
    }

    .login-container button:hover {
      background-color: #5f3ba3;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
    }

    .error {
      background-color: #f8d7da;
      color: #721c24;
      padding: 1rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
      border-left: 4px solid var(--danger-color);
    }

    .signup-link {
      text-align: center;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border-color);
    }

    .signup-link p {
      margin: 0;
      color: #6c757d;
    }

    .signup-link a {
      color: var(--primary-color);
      font-weight: 600;
      text-decoration: none;
    }

    .signup-link a:hover {
      color: var(--secondary-color);
      text-decoration: underline;
    }

    .back-link {
      text-align: center;
      margin-top: 1rem;
    }

    .back-link a {
      color: white;
      font-weight: 500;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: var(--transition);
    }

    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>🎵 Login</h2>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
      </div>

      <button type="submit">Login</button>
    </form>

    <div class="signup-link">
      <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>

    <div class="back-link">
      <a href="index.php">← Back to Home</a>
    </div>
  </div>
</body>
</html>