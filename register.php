<?php
include('config.php');

$errors = array();
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'participant'; // default role

    // Validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    } elseif (strlen($full_name) < 3) {
        $errors[] = "Full name must be at least 3 characters";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (strlen($phone) < 10) {
        $errors[] = "Phone number must be at least 10 characters";
    }

    if (empty($address)) {
        $errors[] = "Address is required";
    }

    if (empty($city)) {
        $errors[] = "City is required";
    }

    if (empty($country)) {
        $errors[] = "Country is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Database checks for duplicates (using prepared statements)
    if (empty($errors)) {
        // Check for duplicate email
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already registered. Please use a different email or login.";
        }
        $stmt->close();

        // Check for duplicate phone
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Phone number already registered with another account.";
            }
            $stmt->close();
        }
    }

    // Insert into database if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address, city, state, country, role, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sssssssss", $full_name, $email, $hashed_password, $phone, $address, $city, $state, $country, $role);

        if ($stmt->execute()) {
            $success = true;
            // Clear form data
            $full_name = $email = $phone = $address = $city = $state = $country = '';
        } else {
            $errors[] = "Registration failed: " . $stmt->error;
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
  <title>User Registration - Music Festival</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background: linear-gradient(135deg, #20c997 0%, #13b489 100%);
      min-height: 100vh;
      padding: 2rem 1rem;
    }

    .register-container {
      background: white;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
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

    .register-container h2 {
      text-align: center;
      margin-bottom: 0.5rem;
      color: var(--secondary-color);
      font-size: 2rem;
    }

    .register-container .subtitle {
      text-align: center;
      color: #6c757d;
      margin-bottom: 2rem;
      font-size: 0.95rem;
    }

    .form-group {
      margin-bottom: 1.2rem;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .register-container label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--dark-text);
      font-size: 0.95rem;
    }

    .register-container input,
    .register-container select {
      width: 100%;
      padding: 0.75rem;
      border: 2px solid var(--border-color);
      border-radius: 6px;
      font-size: 1rem;
      font-family: 'Poppins', sans-serif;
      transition: var(--transition);
      box-sizing: border-box;
    }

    .register-container input:focus,
    .register-container select:focus {
      outline: none;
      border-color: var(--secondary-color);
      box-shadow: 0 0 0 3px rgba(32, 201, 151, 0.1);
    }

    .register-container button {
      width: 100%;
      padding: 0.9rem;
      background-color: var(--secondary-color);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
    }

    .register-container button:hover {
      background-color: #1aa179;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(32, 201, 151, 0.3);
    }

    .success {
      background-color: #d4edda;
      color: #155724;
      padding: 1rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
      border-left: 4px solid var(--success-color);
    }

    .success a {
      color: #155724;
      font-weight: 600;
      text-decoration: none;
    }

    .success a:hover {
      text-decoration: underline;
    }

    .error {
      background-color: #f8d7da;
      color: #721c24;
      padding: 1rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
      border-left: 4px solid var(--danger-color);
    }

    .error ul {
      margin: 0.5rem 0 0 1.2rem;
      padding: 0;
    }

    .error li {
      margin-bottom: 0.3rem;
    }

    .login-link {
      text-align: center;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border-color);
    }

    .login-link a {
      color: var(--secondary-color);
      font-weight: 600;
      text-decoration: none;
    }

    .login-link a:hover {
      color: var(--primary-color);
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
      transition: var(--transition);
    }

    .back-link a:hover {
      text-decoration: underline;
    }

    .hint {
      font-size: 0.85rem;
      color: #6c757d;
      margin-top: 0.3rem;
    }

    @media (max-width: 600px) {
      .form-row {
        grid-template-columns: 1fr;
      }

      .register-container {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="register-container">
    <h2>🎵 Create Account</h2>
    <p class="subtitle">Join our music festival community</p>

    <?php if ($success): ?>
      <div class="success">
        ✅ Registration successful! <br>
        You can now <a href="login.php">login to your account</a>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <strong>Please fix the following errors:</strong>
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateRegistration()">
      <div class="form-group">
        <label for="full_name">Full Name *</label>
        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($full_name ?? '') ?>" required>
        <div class="hint">Your complete name as it should appear in the festival</div>
      </div>

      <div class="form-group">
        <label for="email">Email Address *</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
        <div class="hint">We'll use this to contact you about your registration</div>
      </div>

      <div class="form-group">
        <label for="phone">Phone Number *</label>
        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" placeholder="+1 (555) 000-0000" required>
        <div class="hint">Include country code for international numbers</div>
      </div>

      <div class="form-group">
        <label for="address">Street Address *</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($address ?? '') ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="city">City *</label>
          <input type="text" id="city" name="city" value="<?= htmlspecialchars($city ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="state">State/Province</label>
          <input type="text" id="state" name="state" value="<?= htmlspecialchars($state ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="country">Country *</label>
        <input type="text" id="country" name="country" value="<?= htmlspecialchars($country ?? '') ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password">Password *</label>
          <input type="password" id="password" name="password" required>
          <div class="hint">Minimum 6 characters</div>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password *</label>
          <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
      </div>

      <button type="submit">Create Account</button>
    </form>

    <div class="login-link">
      Already have an account? <a href="login.php">Login here</a>
    </div>

    <div class="back-link">
      <a href="index.php">← Back to Home</a>
    </div>
  </div>

  <script src="assets/js/scripts.js"></script>
  <script>
    function validateRegistration() {
      const fullName = document.getElementById('full_name').value.trim();
      const email = document.getElementById('email').value.trim();
      const phone = document.getElementById('phone').value.trim();
      const address = document.getElementById('address').value.trim();
      const city = document.getElementById('city').value.trim();
      const country = document.getElementById('country').value.trim();
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      if (fullName.length < 3) {
        alert('Full name must be at least 3 characters');
        return false;
      }

      if (!email.includes('@')) {
        alert('Please enter a valid email address');
        return false;
      }

      if (phone.length < 10) {
        alert('Phone number must be at least 10 characters');
        return false;
      }

      if (!address) {
        alert('Street address is required');
        return false;
      }

      if (!city) {
        alert('City is required');
        return false;
      }

      if (!country) {
        alert('Country is required');
        return false;
      }

      if (password.length < 6) {
        alert('Password must be at least 6 characters');
        return false;
      }

      if (password !== confirmPassword) {
        alert('Passwords do not match');
        return false;
      }

      return true;
    }
  </script>
</body>
</html>