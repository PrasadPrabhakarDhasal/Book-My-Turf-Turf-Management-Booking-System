<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    header('Content-Type: application/json');

    $required_fields = ['name', 'mobile', 'email', 'members_count', 'date', 'start_time', 'end_time', 'turf_name'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
            json_response('error', "Field '$field' is required");
        }
    }

    $name = sanitize($_POST['name']);
    $mobile = sanitize($_POST['mobile']);
    $email = sanitize($_POST['email']);
    $members_count = (int)$_POST['members_count'];
    $date = sanitize($_POST['date']);
    $start_time = sanitize($_POST['start_time']);
    $end_time = sanitize($_POST['end_time']);
    $turf_name = sanitize($_POST['turf_name']);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!validate_email($email)) {
        json_response('error', 'Invalid email format');
    }
    if (!validate_mobile($mobile)) {
        json_response('error', 'Mobile number must be 10 digits');
    }
    if ($members_count < 1 || $members_count > 30) {
        json_response('error', 'Members must be between 1 and 30');
    }
    if (!validate_time_format($start_time) || !validate_time_format($end_time)) {
        json_response('error', 'Invalid time format');
    }
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        json_response('error', 'Cannot book for past dates');
    }
    $slot_markers = [];
    $hours = array_merge(range(6, 23), [0, 1]);
    foreach ($hours as $hour) {
        $slot_markers[] = sprintf('%02d:00', $hour);
    }
    $start_index = array_search($start_time, $slot_markers, true);
    $end_index = array_search($end_time, $slot_markers, true);

    if ($start_index === false || $end_index === false || $start_index >= $end_index) {
        json_response('error', 'Please select a valid time range');
    }

    $conflict_stmt = $conn->prepare("SELECT start_time, end_time FROM bookings
        WHERE turf_name = ? AND date = ? AND status IN ('pending', 'approved')");
    $conflict_stmt->bind_param("ss", $turf_name, $date);
    $conflict_stmt->execute();
    $conflict_result = $conflict_stmt->get_result();

    while ($existing = $conflict_result->fetch_assoc()) {
        $existing_start = array_search(substr($existing['start_time'], 0, 5), $slot_markers, true);
        $existing_end = array_search(substr($existing['end_time'], 0, 5), $slot_markers, true);
        if ($existing_start === false || $existing_end === false) {
            continue;
        }
        $overlap = ($start_index < $existing_end) && ($end_index > $existing_start);
        if ($overlap) {
            $conflict_stmt->close();
            json_response('error', 'This time range overlaps with a reserved slot. Please choose another range.');
        }
    }
    $conflict_stmt->close();

    $insert_stmt = $conn->prepare("INSERT INTO bookings (user_id, name, mobile, email, members_count, date, start_time, end_time, turf_name, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $insert_stmt->bind_param("isssissss", $user_id, $name, $mobile, $email, $members_count, $date, $start_time, $end_time, $turf_name);

    if ($insert_stmt->execute()) {
        $booking_id = $insert_stmt->insert_id;
        $insert_stmt->close();
        json_response('success', 'Booking submitted successfully! Your Booking ID: ' . $booking_id, ['booking_id' => $booking_id]);
    }

    $insert_error = $conn->error;
    $insert_stmt->close();
    json_response('error', 'Booking failed: ' . $insert_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_bookings') {
    header('Content-Type: application/json');
    $bookings_query = "SELECT booking_id, name, mobile, email, members_count, date, start_time, end_time, turf_name, status, created_at
                      FROM bookings
                      ORDER BY created_at DESC";
    $result = $conn->query($bookings_query);
    if ($result) {
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        echo json_encode(['status' => 'success', 'bookings' => $bookings]);
        $conn->close();
        exit;
    }
    json_response('error', 'Failed to fetch bookings');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Slot - BOOK MY TURF</title>
    <style>
        :root { --primary:#0f172a; --accent:#2563eb; --bg:#f8fafc; --white:#fff; --danger:#dc2626; --success:#15803d; --muted:#64748b; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
        body { background: var(--bg); color: var(--primary); }
        nav { display:flex; justify-content:space-between; align-items:center; padding:16px 5%; background:var(--white); box-shadow:0 8px 20px rgba(15,23,42,.08); }
        .logo { font-weight: 900; font-size: 1.5rem; }
        .logo span { color: #ef4444; }
        .home-link { color: var(--accent); text-decoration: none; font-weight: 700; }
        .wrap { max-width: 1200px; margin: 24px auto; padding: 0 16px; }
        .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background:var(--white); border:1px solid #e2e8f0; border-radius: 14px; padding: 20px; box-shadow:0 8px 24px rgba(15,23,42,.06); }
        h2 { margin-bottom: 16px; font-size: 1.3rem; }
        .group { margin-bottom: 12px; }
        label { display:block; margin-bottom: 6px; font-weight: 600; }
        input, select { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px; }
        .row { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn { width:100%; border:none; border-radius: 10px; padding:12px; font-weight: 800; color:#fff; background:linear-gradient(135deg,#3b82f6,#1d4ed8); cursor:pointer; margin-top: 6px; }
        .msg { display:none; margin-bottom:12px; border-radius:10px; padding:10px; font-weight:600; text-align:center; }
        .msg.error { display:block; background:#fee2e2; color:#991b1b; }
        .msg.success { display:block; background:#dcfce7; color:#166534; }
        .slots-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }
        .slots-head input { max-width: 230px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border-bottom:1px solid #e2e8f0; padding:10px; text-align:left; }
        th { background:#eff6ff; }
        .status { padding:4px 10px; border-radius:999px; font-size:.82rem; font-weight:700; }
        .available { background:#dcfce7; color:#166534; cursor:pointer; }
        .reserved { background:#fef3c7; color:#92400e; }
        .hint { margin-top:8px; color:var(--muted); font-size:.9rem; }
        @media (max-width: 900px){ .grid { grid-template-columns: 1fr; } .row { grid-template-columns: 1fr; } .slots-head { flex-direction:column; align-items:stretch; } .slots-head input { max-width: none; } }
    </style>
</head>
<body>
    <nav>
        <div class="logo">BOOK MY <span>TURF</span></div>
        <a class="home-link" href="index.html">← Back to Home</a>
    </nav>

    <div class="wrap">
        <div class="grid">
            <section class="card">
                <h2>Booking Form</h2>
                <div id="bookingMsg" class="msg"></div>
                <form id="bookingForm">
                    <div class="group">
                        <label for="name">Name</label>
                        <input id="name" required>
                    </div>
                    <div class="group">
                        <label for="mobile">Contact Number</label>
                        <input id="mobile" maxlength="10" required>
                    </div>
                    <div class="group">
                        <label for="email">Email</label>
                        <input id="email" type="email" required>
                    </div>
                    <div class="group">
                        <label for="membersCount">No. of Members</label>
                        <input id="membersCount" type="number" min="1" max="30" value="1" required>
                    </div>
                    <div class="group">
                        <label for="turfName">Turf</label>
                        <select id="turfName" required>
                            <option value="Morning">Morning</option>
                            <option value="Night">Night</option>
                            <option value="Team Practice">Team Practice</option>
                        </select>
                    </div>
                    <div class="group">
                        <label for="date">Date</label>
                        <input id="date" type="date" required>
                    </div>
                    <div class="row">
                        <div class="group">
                            <label for="startTime">Start Time</label>
                            <select id="startTime" required></select>
                        </div>
                        <div class="group">
                            <label for="endTime">End Time</label>
                            <select id="endTime" required></select>
                        </div>
                    </div>
                    <button class="btn" type="submit">Book Slot</button>
                </form>
                <p class="hint">Click an available slot on the right to auto-fill start/end time.</p>
            </section>

            <section class="card">
                <h2>Slots Today (6 AM to 1 AM)</h2>
                <div class="slots-head">
                    <input id="filterDate" type="date">
                </div>
                <table>
                    <thead><tr><th>Time</th><th>Status</th></tr></thead>
                    <tbody id="slotsBody"><tr><td colspan="2">Loading...</td></tr></tbody>
                </table>
            </section>
        </div>
    </div>

    <script>
        const dateInput = document.getElementById('date');
        const filterDate = document.getElementById('filterDate');
        const turfName = document.getElementById('turfName');
        const startTimeSelect = document.getElementById('startTime');
        const endTimeSelect = document.getElementById('endTime');
        const msg = document.getElementById('bookingMsg');
        let availableSlots = [];

        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        dateInput.value = today;
        filterDate.min = today;
        filterDate.value = today;

        function showMessage(text, type) {
            msg.className = 'msg ' + type;
            msg.textContent = text;
        }

        function time12h(time) {
            const [h, m] = time.split(':').map(Number);
            const ampm = h >= 12 ? 'PM' : 'AM';
            const hour = h % 12 || 12;
            return `${hour}:${String(m).padStart(2,'0')} ${ampm}`;
        }

        function buildTimeOptions(slots) {
            startTimeSelect.innerHTML = '<option value="">Select start</option>';
            endTimeSelect.innerHTML = '<option value="">Select end</option>';

            slots.forEach(slot => {
                if (!slot.is_available) {
                    return;
                }
                startTimeSelect.insertAdjacentHTML('beforeend', `<option value="${slot.start_time}">${time12h(slot.start_time)}</option>`);
            });
        }

        function buildEndOptionsForStart(startTime) {
            endTimeSelect.innerHTML = '<option value="">Select end</option>';
            if (!startTime) {
                return;
            }

            const sorted = [...availableSlots];
            let cursor = startTime;
            const chain = [];
            while (true) {
                const nextSlot = sorted.find(slot => slot.start_time === cursor);
                if (!nextSlot) {
                    break;
                }
                chain.push(nextSlot.end_time);
                cursor = nextSlot.end_time;
            }
            chain.forEach(endTime => {
                endTimeSelect.insertAdjacentHTML('beforeend', `<option value="${endTime}">${time12h(endTime)}</option>`);
            });
        }

        function loadSlots() {
            const turf = turfName.value;
            const date = filterDate.value || today;
            const body = document.getElementById('slotsBody');
            body.innerHTML = '<tr><td colspan="2">Loading slots...</td></tr>';
            fetch(`slots.php?action=get_slots&date=${date}&turf=${encodeURIComponent(turf)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message);
                    if (!data.data.slots.length) {
                        body.innerHTML = '<tr><td colspan="2">No slots found</td></tr>';
                        availableSlots = [];
                        buildTimeOptions([]);
                        return;
                    }
                    availableSlots = data.data.slots.filter(slot => slot.is_available);
                    buildTimeOptions(data.data.slots);

                    // Keep selected values only if still available.
                    const stillValidStart = availableSlots.some(slot => slot.start_time === startTimeSelect.value);
                    const stillValidEnd = availableSlots.some(slot => slot.end_time === endTimeSelect.value);
                    if (!stillValidStart) startTimeSelect.value = '';
                    if (!stillValidEnd) endTimeSelect.value = '';

                    let html = '';
                    data.data.slots.forEach(slot => {
                        const cls = slot.is_available ? 'available' : 'reserved';
                        const click = slot.is_available
                            ? `onclick="pickSlot('${slot.start_time}','${slot.end_time}')"`
                            : '';
                        html += `<tr>
                            <td>${time12h(slot.start_time)} - ${time12h(slot.end_time)}</td>
                            <td><span class="status ${cls}" ${click}>${slot.status}</span></td>
                        </tr>`;
                    });
                    body.innerHTML = html;
                })
                .catch((e) => {
                    body.innerHTML = `<tr><td colspan="2">${e.message || 'Failed to load slots'}</td></tr>`;
                });
        }

        function pickSlot(startTime, endTime) {
            startTimeSelect.value = startTime;
            buildEndOptionsForStart(startTime);
            endTimeSelect.value = endTime;
            document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });
        }
        window.pickSlot = pickSlot;

        startTimeSelect.addEventListener('change', function () {
            buildEndOptionsForStart(this.value);
            endTimeSelect.value = '';
        });

        document.getElementById('bookingForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData();
            if (!startTimeSelect.value || !endTimeSelect.value) {
                showMessage('Please select an available slot time.', 'error');
                return;
            }

            formData.append('action', 'book');
            formData.append('name', document.getElementById('name').value.trim());
            formData.append('mobile', document.getElementById('mobile').value.trim());
            formData.append('email', document.getElementById('email').value.trim());
            formData.append('members_count', document.getElementById('membersCount').value);
            formData.append('turf_name', turfName.value);
            formData.append('date', dateInput.value);
            formData.append('start_time', startTimeSelect.value);
            formData.append('end_time', endTimeSelect.value);

            fetch('booking.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        showMessage(data.message, 'success');
                        this.reset();
                        document.getElementById('membersCount').value = 1;
                        dateInput.value = today;
                        turfName.value = 'Morning';
                        startTimeSelect.value = '';
                        endTimeSelect.value = '';
                        loadSlots();
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(() => showMessage('Booking request failed', 'error'));
        });

        filterDate.addEventListener('change', loadSlots);
        turfName.addEventListener('change', loadSlots);
        document.getElementById('mobile').addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });

        setInterval(loadSlots, 30000);
        loadSlots();
    </script>
</body>
</html>
