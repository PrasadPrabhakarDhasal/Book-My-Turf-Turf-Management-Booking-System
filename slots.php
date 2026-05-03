<?php
include 'db.php';

header('Content-Type: application/json');

// Get Available Slots
if (isset($_GET['action']) && $_GET['action'] === 'get_slots') {
    
    $date = isset($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d');
    $turf_name = isset($_GET['turf']) ? sanitize($_GET['turf']) : 'Premium Turf 1';
    
    // Validate Date
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        json_response('error', 'Invalid date format');
    }
    
    // Time slots from 6 AM to 1 AM (next day), hourly.
    $slot_markers = [];
    $hours = array_merge(range(6, 23), [0, 1]);
    foreach ($hours as $hour) {
        $slot_markers[] = sprintf('%02d:00', $hour);
    }

    $booked_segments = [];
    $booking_stmt = $conn->prepare("SELECT start_time, end_time FROM bookings
        WHERE turf_name = ? AND date = ? AND status IN ('pending', 'approved')");
    $booking_stmt->bind_param("ss", $turf_name, $date);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    while ($booking = $booking_result->fetch_assoc()) {
        $start_marker = substr($booking['start_time'], 0, 5);
        $end_marker = substr($booking['end_time'], 0, 5);
        $start_index = array_search($start_marker, $slot_markers, true);
        $end_index = array_search($end_marker, $slot_markers, true);
        if ($start_index === false || $end_index === false || $start_index >= $end_index) {
            continue;
        }
        for ($i = $start_index; $i < $end_index; $i++) {
            $segment_key = $slot_markers[$i] . '-' . $slot_markers[$i + 1];
            $booked_segments[$segment_key] = true;
        }
    }
    $booking_stmt->close();

    $slots = [];
    for ($i = 0; $i < count($slot_markers) - 1; $i++) {
        $start_time = $slot_markers[$i];
        $end_time = $slot_markers[$i + 1];
        $segment_key = $start_time . '-' . $end_time;
        $is_booked = isset($booked_segments[$segment_key]);

        $slots[] = [
            'start_time' => $start_time,
            'end_time' => $end_time,
            'status' => $is_booked ? 'Reserved' : 'Available',
            'is_available' => !$is_booked
        ];
    }
    
    json_response('success', 'Slots fetched successfully', ['slots' => $slots]);
    
// Get All Turfs
} elseif (isset($_GET['action']) && $_GET['action'] === 'get_turfs') {
    
    $turfs_query = "SELECT DISTINCT turf_name FROM turfs";
    $result = $conn->query($turfs_query);
    
    if ($result) {
        $turfs = [];
        while ($row = $result->fetch_assoc()) {
            $turfs[] = $row['turf_name'];
        }
        json_response('success', 'Turfs fetched successfully', ['turfs' => $turfs]);
    } else {
        json_response('error', 'Failed to fetch turfs');
    }
    
} else {
    json_response('error', 'Invalid request');
}

$conn->close();
?>
