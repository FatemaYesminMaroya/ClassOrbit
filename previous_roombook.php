<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';
$user_dept = $_SESSION['user_dept'] ?? 'N/A';
$user_priority_num = $_SESSION['user_priority_num'] ?? 3; // assuming you store the actual priority_num in session

// Database connection
$conn = new mysqli('localhost', 'root', '', 'classorbit');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Selected date/time
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_time = $_GET['time'] ?? '10:00';
$selected_datetime = "$selected_date $selected_time:00";

// Fetch classrooms
$classrooms = [];
$result = $conn->query("SELECT * FROM Class_room ORDER BY floor_num, room_num");
while ($row = $result->fetch_assoc()) {
    $classrooms[] = $row;
}

// Fetch booked room ids for selected time
$booked_room_ids = [];
$query = "SELECT cr.id 
          FROM `ROOM Booking` rb
          JOIN Checking ch ON rb.Checkingid = ch.id
          JOIN Class_room cr ON ch.Class_roomid = cr.id
          WHERE ch.time = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $selected_datetime);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $booked_room_ids[] = $row['id'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Room | ClassOrbit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #f59e0b;
            --primary-dark: #d97706;
            --bg-dark: #0f172a;
            --card-bg: rgba(15, 23, 42, 0.9);
            --text-light: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #334155;
            --available: #16a34a;
            --booked: #dc2626;
            --success: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: var(--text-light);
            min-height: 100vh;
        }

        nav {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo { display: flex; align-items: center; gap: 0.75rem; font-size: 1.8rem; font-weight: bold; }
        .logo i { color: var(--primary); }
        .logo .highlight { color: var(--primary); }

        .user-section { display: flex; align-items: center; gap: 1rem; }
        .user-avatar {
            width: 42px; height: 42px; background: var(--primary); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.1rem;
        }

        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 120px 2rem 6rem;
        }

        .page-title { text-align: center; font-size: 2.8rem; margin-bottom: 2rem; }

        .date-selector {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 3rem;
            border: 1px solid var(--border);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }

        .date-selector h2 { font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--primary); }

        .date-inputs {
            display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap;
        }

        .date-inputs input {
            padding: 0.8rem 1.2rem; border-radius: 8px; border: 1px solid var(--border);
            background: #1e293b; color: white; font-size: 1rem;
        }

        .btn-check {
            padding: 0.8rem 2rem; background: var(--primary); color: white;
            border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
            margin-top: 1rem; transition: all 0.3s;
        }

        .btn-check:hover { background: var(--primary-dark); transform: translateY(-2px); }

        .section-title { font-size: 2.2rem; margin: 3rem 0 2rem; text-align: center; }

        .classrooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 2rem;
        }

        .classroom-card {
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: all 0.3s;
            position: relative;
        }

        .classroom-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
        }

        .availability-badge {
            position: absolute; top: 15px; right: 15px;
            padding: 0.5rem 1rem; border-radius: 30px; font-size: 0.9rem; font-weight: bold;
        }

        .available { background: var(--available); color: white; }
        .booked { background: var(--booked); color: white; }

        .room-image {
            width: 100%; height: 220px;
            background: linear-gradient(45deg, #1e293b, #334155);
            background-size: cover; background-position: center;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.8rem; font-weight: bold;
        }

        .room-info { padding: 1.8rem; }

        .room-info h3 { color: var(--primary); font-size: 1.5rem; margin-bottom: 1rem; }

        .features {
            display: flex; flex-wrap: wrap; gap: 1rem; margin: 1.2rem 0;
            font-size: 0.95rem; color: var(--text-muted);
        }

        .feature { display: flex; align-items: center; gap: 0.5rem; }
        .feature i { color: var(--primary); }

        .book-btn {
            width: 100%; padding: 1rem; background: var(--primary);
            color: white; border: none; border-radius: 8px; font-weight: 600;
            font-size: 1.1rem; cursor: pointer; transition: all 0.3s; margin-top: 1rem;
        }

        .book-btn:hover { background: var(--primary-dark); }
        .book-btn.disabled { background: #475569; cursor: not-allowed; opacity: 0.7; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 2000;
            align-items: center; justify-content: center;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: var(--card-bg);
            padding: 2.5rem; border-radius: 20px; width: 90%; max-width: 600px;
            border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        }

        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);
        }

        .modal-header h2 { color: var(--primary); font-size: 1.8rem; }

        .close-modal {
            background: none; border: none; font-size: 1.8rem; color: #94a3b8;
            cursor: pointer; transition: color 0.3s;
        }

        .close-modal:hover { color: #ef4444; }

        .modal-body label {
            display: block; margin: 1rem 0 0.5rem; color: var(--text-muted); font-weight: 500;
        }

        .modal-body input, .modal-body textarea {
            width: 100%; padding: 0.8rem 1rem; border-radius: 8px;
            border: 1px solid var(--border); background: #1e293b; color: white;
            font-size: 1rem;
        }

        .modal-body textarea { resize: vertical; min-height: 100px; }

        .modal-footer {
            margin-top: 2rem; text-align: right;
        }

        .btn-submit {
            padding: 1rem 2.5rem; background: var(--primary); color: white;
            border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover { background: var(--primary-dark); transform: translateY(-3px); }

        .message {
            padding: 1rem; margin: 1rem 0; border-radius: 8px; text-align: center; font-weight: 500;
        }

        .success { background: rgba(16, 185, 129, 0.2); color: var(--success); border: 1px solid var(--success); }
        .error { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid #ef4444; }

        @media (max-width: 768px) {
            .date-inputs { flex-direction: column; align-items: center; }
            .classrooms-grid { grid-template-columns: 1fr; }
            .modal-content { padding: 1.5rem; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-container">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Class<span class="highlight">Orbit</span></span>
        </div>
        <div class="user-section">
            <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
            <span style="color: #cbd5e1;"><?php echo htmlspecialchars($user_name); ?></span>
            <a href="dashboard.php" style="color: #cbd5e1; text-decoration: none; margin-left: 1rem;">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>
</nav>

<main>
    <h1 class="page-title">Book a Classroom</h1>

    <div class="date-selector">
        <h2>Select Date & Time</h2>
        <form method="GET">
            <div class="date-inputs">
                <div>
                    <label>Date</label><br>
                    <input type="date" name="date" value="<?php echo $selected_date; ?>" required>
                </div>
                <div>
                    <label>Time</label><br>
                    <input type="time" name="time" value="<?php echo $selected_time; ?>" required>
                </div>
            </div>
            <br>
            <button type="submit" class="btn-check">Check Availability</button>
        </form>
        <p style="margin-top: 1rem;">
            Selected: <strong><?php echo date('l, F j, Y', strtotime($selected_date)); ?> at <?php echo date('g:i A', strtotime($selected_time)); ?></strong>
        </p>
    </div>

    <h2 class="section-title">Available Rooms</h2>

    <div class="classrooms-grid">
        <?php foreach ($classrooms as $room): 
            $is_booked = in_array($room['id'], $booked_room_ids);
        ?>
            <div class="classroom-card">
                <div class="availability-badge <?php echo $is_booked ? 'booked' : 'available'; ?>">
                    <?php echo $is_booked ? 'Booked' : 'Available'; ?>
                </div>

                <div class="room-image" style="background-image: url('assets/rooms/room<?php echo $room['room_num']; ?>.jpg');">
                    <span>Room <?php echo $room['room_num']; ?></span>
                </div>

                <div class="room-info">
                    <h3>Room <?php echo $room['room_num']; ?> - Floor <?php echo $room['floor_num']; ?></h3>
                    <p><strong>Capacity:</strong> <?php echo $room['capacitie']; ?> seats</p>
                    <p><strong>Type:</strong> <?php echo ucfirst($room['Typetype_name']); ?></p>

                    <div class="features">
                        <span class="feature"><i class="fas fa-projector"></i> <?php echo ucwords($room['projector']); ?></span>
                        <span class="feature"><i class="fas fa-snowflake"></i> <?php echo ucwords($room['AC']); ?></span>
                        <span class="feature"><i class="fas fa-volume-up"></i> <?php echo ucwords($room['speeker']); ?></span>
                    </div>

                    <?php if (!empty($room['any_prob'])): ?>
                        <p style="color: #fca5a5; margin-top: 1rem;"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($room['any_prob']); ?></p>
                    <?php endif; ?>

                    <button class="book-btn <?php echo $is_booked ? 'disabled' : ''; ?>"
                            <?php echo $is_booked ? 'disabled' : ''; ?>
                            onclick="<?php echo !$is_booked ? "openBookingModal({$room['id']}, '{$room['room_num']}', '{$selected_date}', '{$selected_time}')" : ''; ?>">
                        <?php echo $is_booked ? 'Already Booked' : 'Book Now'; ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Booking Modal -->
<div class="modal" id="bookingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirm Booking</h2>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p><strong>Room:</strong> <span id="modalRoomNum"></span></p>
            <p><strong>Date & Time:</strong> <span id="modalDateTime"></span></p>
            <p><strong>User:</strong> <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_email); ?>)</p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($user_dept); ?></p>

            <div id="message"></div>

            <form id="bookingForm">
                <input type="hidden" name="room_id" id="room_id">
                <input type="hidden" name="datetime" id="datetime">

                <label for="reason">Reason for Booking *</label>
                <textarea name="reason" id="reason" required placeholder="e.g., Department meeting, Club event, Lecture"></textarea>

                <label for="urgent_needs">Any Urgent Needs? (Optional)</label>
                <textarea name="urgent_needs" id="urgent_needs" placeholder="e.g., Extra chairs, microphone, whiteboard markers"></textarea>

                <div class="modal-footer">
                    <button type="submit" class="btn-submit">Submit Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openBookingModal(roomId, roomNum, date, time) {
        document.getElementById('room_id').value = roomId;
        document.getElementById('modalRoomNum').textContent = roomNum;
        document.getElementById('modalDateTime').textContent = new Date(date + ' ' + time).toLocaleString();
        document.getElementById('datetime').value = date + ' ' + time + ':00';
        document.getElementById('bookingModal').classList.add('active');
        document.getElementById('message').innerHTML = '';
        document.getElementById('bookingForm').reset();
        document.getElementById('reason').focus();
    }

    function closeModal() {
        document.getElementById('bookingModal').classList.remove('active');
    }

    // Close modal when clicking outside
    document.getElementById('bookingModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Submit booking via AJAX
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'book_room');

        fetch('process_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const msgDiv = document.getElementById('message');
            if (data.success) {
                msgDiv.innerHTML = `<div class="message success">${data.message}</div>`;
                setTimeout(() => {
                    closeModal();
                    location.reload(); // Refresh to update availability
                }, 2000);
            } else {
                msgDiv.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        })
        .catch(() => {
            document.getElementById('message').innerHTML = `<div class="message error">Network error. Please try again.</div>`;
        });
    });
</script>

</body>
</html>