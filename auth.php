<?php
include 'db.php';

header('Content-Type: application/json');

// ==================== SIGNUP ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {

    // Get inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Validate full name
    if (empty($full_name) || strlen($full_name) < 3) {
        json_response('error', 'Full name must be at least 3 characters');
    }

    // Validate email
    if (empty($email) || !validate_email($email)) {
        json_response('error', 'Please enter a valid email address');
    }

    // Validate password
    if (empty($password) || strlen($password) < 6) {
        json_response('error', 'Password must be at least 6 characters');
    }

    // Confirm passwords match
    if ($password !== $confirm) {
        json_response('error', 'Passwords do not match');
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        json_response('error', 'An account with this email already exists. Please login instead.');
    }
    $stmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $full_name, $email, $hashed_password);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        // Start session and set user data
        $_SESSION['user_id']   = $user_id;
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_email'] = $email;

        json_response('success', 'Account created successfully! Welcome, ' . $full_name, [
            'user_id'   => $user_id,
            'full_name' => $full_name,
            'email'     => $email
        ]);
    } else {
        json_response('error', 'Registration failed. Please try again.');
    }
    $stmt->close();
}

// ==================== LOGIN ====================
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || !validate_email($email)) {
        json_response('error', 'Please enter a valid email address');
    }

    if (empty($password)) {
        json_response('error', 'Please enter your password');
    }

    // Look up user
    $stmt = $conn->prepare("SELECT user_id, full_name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        json_response('error', 'No account found with this email. Please sign up first.');
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        json_response('error', 'Incorrect password. Please try again.');
    }

    // Set session
    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];

    json_response('success', 'Login successful! Welcome back, ' . $user['full_name'], [
        'user_id'   => $user['user_id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email']
    ]);
}

// ==================== LOGOUT ====================
elseif (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: index.html');
    exit;
}

// ==================== CHECK SESSION ====================
elseif (isset($_GET['action']) && $_GET['action'] === 'check') {
    if (isset($_SESSION['user_id'])) {
        json_response('success', 'User is logged in', [
            'user_id'   => $_SESSION['user_id'],
            'full_name' => $_SESSION['user_name'],
            'email'     => $_SESSION['user_email']
        ]);
    } else {
        json_response('error', 'Not logged in');
    }
}

// ==================== INVALID REQUEST ====================
else {
    json_response('error', 'Invalid request');
}

$conn->close();
?>
