<?php
include 'db.php';

// Simple Admin Authentication
$is_admin = false;
if (isset($_SESSION['admin_id'])) {
    $is_admin = true;
}

// Handle Login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $login_query = "SELECT admin_id, password FROM admin_users WHERE username = '$username'";
    $result = $conn->query($login_query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $is_admin = true;
            header('Location: admin.php');
            exit;
        } else {
            $login_error = 'Invalid password.';
        }
    } else {
        $login_error = 'Invalid username.';
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle Update Booking Status (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    
    if (!$is_admin) {
        json_response('error', 'Unauthorized');
    }
    
    $booking_id = sanitize($_POST['booking_id']);
    $status = sanitize($_POST['status']);
    
    if (!in_array($status, ['pending', 'approved', 'cancelled'])) {
        json_response('error', 'Invalid status');
    }
    
    $update_query = "UPDATE bookings SET status = '$status' WHERE booking_id = $booking_id";
    
    if ($conn->query($update_query)) {
        json_response('success', 'Booking status updated');
    } else {
        json_response('error', 'Failed to update status');
    }
}

// Handle Delete Booking (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_booking') {
    header('Content-Type: application/json');
    
    if (!$is_admin) {
        json_response('error', 'Unauthorized');
    }
    
    $booking_id = sanitize($_POST['booking_id']);
    
    $delete_query = "DELETE FROM bookings WHERE booking_id = $booking_id";
    
    if ($conn->query($delete_query)) {
        json_response('success', 'Booking deleted successfully');
    } else {
        json_response('error', 'Failed to delete booking');
    }
}

// Fetch All Bookings
$bookings = [];
if ($is_admin) {
    $bookings_query = "SELECT booking_id, name, mobile, email, members_count, date, CONCAT(start_time, ' - ', end_time) as time_slot, turf_name, status, created_at 
                      FROM bookings 
                      ORDER BY created_at DESC";
    
    $result = $conn->query($bookings_query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Turf Booking</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .login-form {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .login-form h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background: #c41f0f;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 1rem;
        }

        .admin-header h1 {
            color: var(--primary);
        }

        .btn-logout {
            padding: 0.6rem 1.2rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #c41f0f;
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .bookings-table thead {
            background: linear-gradient(135deg, var(--accent) 0%, #c41f0f 100%);
            color: white;
        }

        .bookings-table th,
        .bookings-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .bookings-table tbody tr:hover {
            background: #f5f5f5;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background: #c82333;
        }

        .btn-delete {
            background: #6c757d;
            color: white;
        }

        .btn-delete:hover {
            background: #5a6268;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<nav>
    <div class="logo">BOOK MY <span>TURF</span></div>
    <div class="nav-links">
        <a href="index.html">Home</a>
        <a href="admin.php" style="color: var(--accent); font-weight: bold;">Admin</a>
    </div>
</nav>

<div class="admin-container">
    <?php if (!$is_admin): ?>
        <div class="login-form">
            <h2>Admin Login</h2>
            <?php if ($login_error): ?>
                <div class="alert alert-error" style="margin-bottom: 1rem;"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
            <!-- <p style="text-align: center; margin-top: 1rem; color: #666;">Demo: username=<strong>admin</strong>, password=<strong>admin123</strong></p> -->
        </div>
    <?php else: ?>
        <div class="admin-header">
            <h1>📊 Booking Management</h1>
            <a href="?logout=1" class="btn-logout">Logout</a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (count($bookings) > 0): ?>
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Members</th>
                        <th>Date</th>
                        <th>Time Slot</th>
                        <th>Turf</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><strong>#<?php echo $booking['booking_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($booking['email']); ?></td>
                            <td><?php echo (int)$booking['members_count']; ?></td>
                            <td><?php echo date('d M Y', strtotime($booking['date'])); ?></td>
                            <td><?php echo $booking['time_slot']; ?></td>
                            <td><?php echo htmlspecialchars($booking['turf_name']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <button class="btn-action btn-approve" onclick="updateStatus(<?php echo $booking['booking_id']; ?>, 'approved')">Approve</button>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] !== 'cancelled'): ?>
                                        <button class="btn-action btn-cancel" onclick="updateStatus(<?php echo $booking['booking_id']; ?>, 'cancelled')">Cancel</button>
                                    <?php endif; ?>
                                    <button class="btn-action btn-delete" onclick="deleteBooking(<?php echo $booking['booking_id']; ?>)">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-success">
                ✓ No bookings found.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function updateStatus(bookingId, status) {
    if (confirm('Are you sure you want to ' + status + ' this booking?')) {
        fetch('admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=update_status&booking_id=' + bookingId + '&status=' + status
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Status updated successfully');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function deleteBooking(bookingId) {
    if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
        fetch('admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=delete_booking&booking_id=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Booking deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

</body>
</html>
