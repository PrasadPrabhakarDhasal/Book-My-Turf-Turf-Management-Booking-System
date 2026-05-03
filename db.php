<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'turf_booking');

// Create Connection (without selecting DB first)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check Connection
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Create database automatically if it does not exist.
if (!$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die(json_encode(['status' => 'error', 'message' => 'Failed to create database: ' . $conn->error]));
}

if (!$conn->select_db(DB_NAME)) {
    die(json_encode(['status' => 'error', 'message' => 'Failed to select database: ' . $conn->error]));
}

// Ensure users table exists for signup/login functionality.
$createUsersTable = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createUsersTable)) {
    die(json_encode(['status' => 'error', 'message' => 'Failed to create users table: ' . $conn->error]));
}

// Ensure admin_users table exists.
$createAdminTable = "CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createAdminTable)) {
    die(json_encode(['status' => 'error', 'message' => 'Failed to create admin_users table: ' . $conn->error]));
}

// Insert default admin if not exists (password: admin123)
$conn->query("INSERT INTO admin_users (username, password) VALUES ('admin', '\$2y\$10\$hI.v8xNHov64F4iR9rfhHefxFIUDC9C67K2qmhLZdt3CBgKx4w2Nm') ON DUPLICATE KEY UPDATE password = VALUES(password)");

// Ensure turfs table exists.
$createTurfsTable = "CREATE TABLE IF NOT EXISTS turfs (
    turf_id INT AUTO_INCREMENT PRIMARY KEY,
    turf_name VARCHAR(100) NOT NULL UNIQUE,
    location VARCHAR(200),
    price_per_hour DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createTurfsTable)) {
    die(json_encode(['status' => 'error', 'message' => 'Failed to create turfs table: ' . $conn->error]));
}

$conn->query("INSERT INTO turfs (turf_name, location, price_per_hour) VALUES
    ('City Cricket Turf', 'Downtown Sports Complex', 1200.00),
    ('Neighborhood Turf', 'Green Park Area', 900.00),
    ('Team Practice Turf', 'Stadium Road', 1500.00)
    ON DUPLICATE KEY UPDATE turf_name = turf_name");

// Ensure bookings table exists.
$createBookingsTable = "CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    email VARCHAR(150) NOT NULL,
    members_count INT NOT NULL DEFAULT 1,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    turf_name VARCHAR(100) NOT NULL,
    status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createBookingsTable)) {
    die(json_encode(['status' => 'error', 'message' => 'Failed to create bookings table: ' . $conn->error]));
}

// Add members_count to old booking tables only if missing.
$columnCheck = $conn->query("SHOW COLUMNS FROM bookings LIKE 'members_count'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE bookings ADD COLUMN members_count INT NOT NULL DEFAULT 1 AFTER email");
}

// Set Charset
$conn->set_charset('utf8mb4');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session Management
session_start();

// Helper Function to Escape Input
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8'));
}

// Helper Function for JSON Response
function json_response($status, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Helper Function for Validation
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_mobile($mobile) {
    return preg_match('/^[0-9]{10}$/', $mobile);
}

function validate_time_format($time) {
    return preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $time);
}

// Helper Function to Get Logged In User
function get_logged_in_user() {
    if (isset($_SESSION['user_id'])) {
        return [
            'user_id'   => $_SESSION['user_id'],
            'full_name' => $_SESSION['user_name'],
            'email'     => $_SESSION['user_email']
        ];
    }
    return null;
}

?>
