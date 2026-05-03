<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'turf_booking';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass);

if ($mysqli->connect_error) {
    die('<h2>Database connection failed:</h2><p>' . htmlspecialchars($mysqli->connect_error, ENT_QUOTES, 'UTF-8') . '</p>');
}

$mysqli->set_charset('utf8mb4');

$queries = [
    "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "USE `$dbName`",
    "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admin_users (
        admin_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "INSERT INTO admin_users (username, password)
     VALUES ('admin', '$2y$10$hI.v8xNHov64F4iR9rfhHefxFIUDC9C67K2qmhLZdt3CBgKx4w2Nm')
     ON DUPLICATE KEY UPDATE password = VALUES(password)",
    "CREATE TABLE IF NOT EXISTS turfs (
        turf_id INT AUTO_INCREMENT PRIMARY KEY,
        turf_name VARCHAR(100) NOT NULL UNIQUE,
        location VARCHAR(200),
        price_per_hour DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "INSERT INTO turfs (turf_name, location, price_per_hour) VALUES
        ('City Cricket Turf', 'Downtown Sports Complex', 1200.00),
        ('Neighborhood Turf', 'Green Park Area', 900.00),
        ('Team Practice Turf', 'Stadium Road', 1500.00)
     ON DUPLICATE KEY UPDATE turf_name = turf_name",
    "CREATE TABLE IF NOT EXISTS bookings (
        booking_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        name VARCHAR(100) NOT NULL,
        mobile VARCHAR(15) NOT NULL,
        email VARCHAR(150) NOT NULL,
        date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        turf_name VARCHAR(100) NOT NULL,
        status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$errors = [];
foreach ($queries as $sql) {
    if (!$mysqli->query($sql)) {
        $errors[] = $mysqli->error;
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; padding: 28px; }
        .card { max-width: 780px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 22px; }
        h1 { margin-top: 0; }
        .ok { color: #166534; font-weight: 700; }
        .err { color: #991b1b; font-weight: 700; }
        ul { margin-top: 10px; }
        a { color: #2563eb; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>BOOK MY TURF - Setup</h1>
        <?php if (empty($errors)): ?>
            <p class="ok">Database and tables are ready.</p>
            <p>You can now use signup/login:</p>
            <ul>
                <li><a href="signup.html">Open Sign Up</a></li>
                <li><a href="login.html">Open Login</a></li>
                <li><a href="index.html">Go to Home</a></li>
            </ul>
        <?php else: ?>
            <p class="err">Setup completed with errors:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
