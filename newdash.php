<?php
session_start();

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

/* ================= USER DATA ================= */
$user_id       = $_SESSION['user_id'];
$user_name     = $_SESSION['user_name'] ?? 'User';
$user_email    = $_SESSION['user_email'];
$user_role     = $_SESSION['user_role'] ?? 'User';
$user_priority = $_SESSION['user_priority'] ?? 'Standard';

/* ================= AUTO PRIORITY BASED ON ROLE ================= */
$auto_priority = 3; // Default: Low for Student
if ($user_role == 'Faculty') {
    $auto_priority = 1; // High
} elseif ($user_role == 'Club') {
    $auto_priority = 2; // Medium
} // Admin can default to High if needed, but set to 1
elseif ($user_role == 'Admin') {
    $auto_priority = 1;
}

/* ================= DB ================= */
$conn = new mysqli("localhost", "root", "", "classorbit");
if ($conn->connect_error) {
    die("DB Connection failed");
}

/* ================= HANDLE POST RESERVE ================= */
if (isset($_POST['action']) && $_POST['action'] == 'reserve') {
    $dpt = trim($_POST['dpt']);
    $email = trim($_POST['email']);
    $reason = trim($_POST['reason']);
    $checkingid = (int)$_POST['checkingid'];
    $priority = $auto_priority; // Auto-set from session/role
    $urgent = trim($_POST['urgent'] ?? '');

    // Check for existing booking
    $check_stmt = $conn->prepare("SELECT id, priority_id, status FROM Room_Booking WHERE checking_id = ? AND status IN ('pending', 'approved')");
    $check_stmt->bind_param("i", $checkingid);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    $bumped = false;
    if ($check_row = $check_result->fetch_assoc()) {
        $existing_priority = $check_row['priority_id'];
        $existing_status = $check_row['status'];
        if ($priority >= $existing_priority) {
            echo json_encode(['success' => false, 'error' => 'Slot already booked by equal or higher priority user.']);
            $check_stmt->close();
            exit();
        } else {
            // Bump existing booking to cancelled
            $update_bump = $conn->prepare("UPDATE Room_Booking SET status = 'cancelled' WHERE id = ?");
            $update_bump->bind_param("i", $check_row['id']);
            $update_bump->execute();
            $update_bump->close();
            // If it was approved, restore availability
            if ($existing_status == 'approved') {
                $update_avail = $conn->prepare("UPDATE Checking SET is_available = 1 WHERE id = ?");
                $update_avail->bind_param("i", $checkingid);
                $update_avail->execute();
                $update_avail->close();
            }
            $bumped = true;
        }
    }
    $check_stmt->close();

    // Insert new booking as pending
    $stmt = $conn->prepare("INSERT INTO Room_Booking (user_id, dept, email, reason, checking_id, priority_id, urgent_needs, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("isssiss", $user_id, $dpt, $email, $reason, $checkingid, $priority, $urgent);

    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        // Do not update availability yet
        echo json_encode(['success' => true, 'bumped' => $bumped, 'booking_id' => $booking_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit();
}

/* ================= HANDLE APPROVE BOOKING ================= */
if (isset($_POST['action']) && $_POST['action'] == 'approve') {
    $booking_id = (int)$_POST['booking_id'];
    $stmt = $conn->prepare("SELECT rb.id, rb.status, rb.checking_id, rb.priority_id FROM Room_Booking rb WHERE rb.id = ? AND rb.user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['status'] !== 'pending') {
            echo json_encode(['success' => false, 'error' => 'Booking is no longer pending.']);
        } else {
            $checking_id = $row['checking_id'];
            $current_priority = $row['priority_id'];

            // Check if any approved booking exists for this slot
            $check_approved = $conn->prepare("SELECT id, priority_id FROM Room_Booking WHERE checking_id = ? AND status = 'approved'");
            $check_approved->bind_param("i", $checking_id);
            $check_approved->execute();
            $approved_result = $check_approved->get_result();

            if ($approved_row = $approved_result->fetch_assoc()) {
                $existing_priority = $approved_row['priority_id'];
                if ($current_priority >= $existing_priority) {
                    // Cannot bump, cancel this
                    $update_cancel = $conn->prepare("UPDATE Room_Booking SET status = 'cancelled' WHERE id = ?");
                    $update_cancel->bind_param("i", $booking_id);
                    $update_cancel->execute();
                    $update_cancel->close();
                    echo json_encode(['success' => false, 'error' => 'Slot already approved by equal or higher priority user.']);
                } else {
                    // Bump existing approved to cancelled, restore availability
                    $update_bump = $conn->prepare("UPDATE Room_Booking SET status = 'cancelled' WHERE id = ?");
                    $update_bump->bind_param("i", $approved_row['id']);
                    $update_bump->execute();
                    $update_bump->close();

                    $update_avail_restore = $conn->prepare("UPDATE Checking SET is_available = 1 WHERE id = ?");
                    $update_avail_restore->bind_param("i", $checking_id);
                    $update_avail_restore->execute();
                    $update_avail_restore->close();

                    // Now approve this one
                    $update_approve = $conn->prepare("UPDATE Room_Booking SET status = 'approved' WHERE id = ?");
                    $update_approve->bind_param("i", $booking_id);
                    $update_approve->execute();

                    // Update availability to unavailable
                    $update_avail = $conn->prepare("UPDATE Checking SET is_available = 0 WHERE id = ?");
                    $update_avail->bind_param("i", $checking_id);
                    $update_avail->execute();
                    $update_avail->close();

                    $update_approve->close();
                    echo json_encode(['success' => true]);
                }
            } else {
                // No approved, approve this
                $update_approve = $conn->prepare("UPDATE Room_Booking SET status = 'approved' WHERE id = ?");
                $update_approve->bind_param("i", $booking_id);
                $update_approve->execute();

                // Update availability
                $update_avail = $conn->prepare("UPDATE Checking SET is_available = 0 WHERE id = ?");
                $update_avail->bind_param("i", $checking_id);
                $update_avail->execute();
                $update_avail->close();

                $update_approve->close();
                echo json_encode(['success' => true]);
            }
            $check_approved->close();
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Booking not found or not pending.']);
    }
    $stmt->close();
    exit();
}

/* ================= FETCH AVAILABLE SLOTS ================= */
if (isset($_GET['get_slots']) && isset($_GET['room'])) {
    $room_num = $_GET['room'];
    $stmt = $conn->prepare("SELECT id FROM Class_room WHERE room_num = ?");
    $stmt->bind_param("i", $room_num);
    $stmt->execute();
    $result = $stmt->get_result();
    $slots = [];
    if ($row = $result->fetch_assoc()) {
        $class_id = $row['id'];
        $stmt = $conn->prepare("SELECT id, start_time, end_time FROM Checking WHERE class_room_id = ? AND is_available = 1");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $book_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM Room_Booking WHERE checking_id = ? AND status = 'approved'");
            $book_stmt->bind_param("i", $row['id']);
            $book_stmt->execute();
            $book_res = $book_stmt->get_result()->fetch_assoc();
            if ($book_res['cnt'] == 0) {
                $start_formatted = date('M d, Y h:i A', strtotime($row['start_time']));
                $end_formatted = date('h:i A', strtotime($row['end_time']));
                $slots[] = ['id' => $row['id'], 'time' => "{$start_formatted} - {$end_formatted}"];
            }
            $book_stmt->close();
        }
        $stmt->close();
    }
    echo json_encode($slots);
    exit();
}

/* ================= FETCH BOOKING STATUS ================= */
if (isset($_GET['get_status']) && isset($_GET['booking_id'])) {
    $booking_id = (int)$_GET['booking_id'];
    $stmt = $conn->prepare("SELECT status FROM Room_Booking WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['status' => $row['status']]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
    $stmt->close();
    exit();
}

/* ================= FETCH PROFILE PIC ================= */
$user_pic = $_SESSION['profile_pic'] ?? 'assets/default.jpg';
if (!isset($_SESSION['profile_pic'])) {
    $pic_query = null;
    if ($user_role == 'Student') {
        $pic_query = "SELECT pic FROM Student WHERE id = ?";
    } elseif ($user_role == 'Club') {
        $pic_query = "SELECT pic FROM Club WHERE id = ?";
    } elseif ($user_role == 'Faculty') {
        $pic_query = "SELECT pic FROM Faculty WHERE id = ?";
    } elseif ($user_role == 'Admin') {
        $pic_query = "SELECT pic FROM Admin WHERE id = ?";
    }

    if ($pic_query) {
        $stmt = $conn->prepare($pic_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_pic = $row['pic'] ?? 'assets/default.jpg';
            $_SESSION['profile_pic'] = $user_pic;
        }
        $stmt->close();
    }
}

/* ================= CLASSROOMS ================= */
$classrooms = [];
$res = $conn->query("SELECT * FROM Class_room ORDER BY floor_num, room_num");
while ($row = $res->fetch_assoc()) {
    $classrooms[] = $row;
}

/* ================= BOOKINGS → CALENDAR ================= */
$events = [];
$where = "";
$params = [];
$types = "";

if ($user_role !== "Admin") {
    $where = "WHERE rb.email = ?";
    $params[] = $user_email;
    $types = "s";
}

$sql = "SELECT rb.*, cr.room_num, cr.floor_num, ch.start_time, ch.end_time, p.priority_id, p.description as priority_name FROM Room_Booking rb 
        JOIN Checking ch ON rb.checking_id = ch.id 
        JOIN Class_room cr ON ch.class_room_id = cr.id 
        JOIN priority p ON rb.priority_id = p.priority_id 
        $where AND rb.status = 'approved' ORDER BY ch.start_time";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $color = ($row['priority_id'] == 1) ? "#16a34a" : (($row['priority_id'] == 2) ? "#ca8a04" : "#dc2626");
    $events[] = [
        "title" => "Room {$row['room_num']} | {$row['reason']}",
        "start" => $row['start_time'],
        "end" => $row['end_time'],
        "backgroundColor" => $color,
        "borderColor" => $color,
        "textColor" => "#fff",
        "extendedProps" => [
            "room" => $row['room_num'],
            "floor" => $row['floor_num'],
            "reason" => $row['reason']
        ]
    ];
}


/* ================= FETCH NOTIFICATIONS WITH CANCEL REASONS ================= */
$notifications = [];
$sql_notif = "SELECT rb.*, cr.room_num, ch.start_time as time, ch.end_time, p.description as priority_name 
              FROM Room_Booking rb 
              JOIN Checking ch ON rb.checking_id = ch.id 
              JOIN Class_room cr ON ch.class_room_id = cr.id 
              JOIN priority p ON rb.priority_id = p.priority_id 
              WHERE rb.user_id = ? 
              ORDER BY ch.start_time DESC 
              LIMIT 10";
$stmt_notif = $conn->prepare($sql_notif);
$stmt_notif->bind_param("i", $user_id);
$stmt_notif->execute();
$result_notif = $stmt_notif->get_result();
while ($row = $result_notif->fetch_assoc()) {
    $status_icon = $row['status'] == 'approved' ? 'fa-check-circle' : 
                   ($row['status'] == 'pending' ? 'fa-clock' : 
                   ($row['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-exclamation-triangle'));
    $status_color = $row['status'] == 'approved' ? '#10b981' : 
                    ($row['status'] == 'pending' ? '#f59e0b' : '#ef4444');

    $cancel_msg = '';
    if ($row['status'] == 'cancelled') {
        $cancel_reason = !empty($row['cancel_reason']) ? $row['cancel_reason'] : "Your booking was cancelled. We apologize for the inconvenience.";
        $cancel_msg = '<br><small style="color: #ef4444; font-style: italic;">' . htmlspecialchars($cancel_reason) . '</small>';
    }

    $notifications[] = [
        'room_num' => $row['room_num'],
        'reason' => $row['reason'],
        'time' => $row['time'],
        'end_time' => $row['end_time'],
        'status' => $row['status'],
        'priority_name' => $row['priority_name'],
        'status_icon' => $status_icon,
        'status_color' => $status_color,
        'cancel_msg' => $cancel_msg
    ];
}
$stmt_notif->close();

$notification_count = count($notifications);


/* ================= ROOM AVAILABILITY ================= */
$availability = [];
foreach ($classrooms as $classroom) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as cnt 
        FROM Checking ch 
        LEFT JOIN Room_Booking rb ON ch.id = rb.checking_id AND rb.status = 'approved' 
        WHERE ch.class_room_id = ? AND ch.is_available = 1 AND rb.id IS NULL
    ");
    $stmt->bind_param("i", $classroom['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $availability[$classroom['id']] = $row['cnt'] > 0;
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Professional Dashboard | ClassOrbit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@500;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">


</head>

<body>

    <nav>
        <a href="#" class="logo-section">
            <i class="fas fa-graduation-cap" style="color: var(--primary); font-size: 1.7rem;"></i>
            <h1 class="logoName">Class<span>Orbit</span></h1>
        </a>

        <div class="nav-actions">
            <div class="notification-chip">
                <i class="fas fa-bell" id="notificationBtn"></i>
                <span class="notification-badge <?= $notification_count > 0 ? 'show' : '' ?>" id="notificationCount"><?= $notification_count ?></span>
            </div>
            <div class="profile-chip">
                <img src="uploads/users/<?= $user_pic ?>" alt="Profile">
                <span><?= htmlspecialchars($user_name) ?></span>
            </div>
            <i class="fas fa-bars-staggered menu-btn" id="menuToggle"></i>
        </div>
    </nav>

    <!-- NOTIFICATION DROPDOWN -->
    <div id="notificationDropdown" class="notification-dropdown">
        <div class="notification-dropdown-header">
            <h4>My Bookings</h4>
            <button class="notification-dropdown-close" onclick="closeNotificationDropdown()">&times;</button>
        </div>
        <div id="notificationList">
            <?php if (empty($notifications)): ?>
                <div class="notification-empty">
                    <p>No bookings yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif):
                    $status_icon = $notif['status'] == 'approved' ? 'fa-check-circle' : ($notif['status'] == 'pending' ? 'fa-clock' : ($notif['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-exclamation-triangle'));
                    $status_color = $notif['status'] == 'approved' ? '#10b981' : ($notif['status'] == 'pending' ? '#f59e0b' : '#ef4444');
                    $cancel_msg = ($notif['status'] == 'cancelled') ? '<br><small style="color: #ef4444; font-style: italic;">Your booking was rescheduled due to a faculty request. We apologize for the inconvenience.</small>' : '';
                ?>
                    <div class="notification-item">
                        <i class="fas <?= $status_icon ?>" style="color: <?= $status_color ?>;"></i>
                        <div class="notification-item-content">
                            <p>Room <?= htmlspecialchars($notif['room_num']) ?> | <?= htmlspecialchars($notif['reason']) ?></p>
                            <small>
                                <?= date('M d, Y h:i A', strtotime($notif['time'])) ?> - <?= date('h:i A', strtotime($notif['end_time'])) ?>
                                | Status: <?= ucfirst($notif['status']) ?>
                                <?= $notif['cancel_msg'] ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-link active"><i class="fas fa-house"></i> Dashboard</a>
        <!-- <a href="book_room.php" class="sidebar-link"><i class="fas fa-calendar-plus"></i> Reserve Room</a> -->
        <a href="my_schedule.php" class="sidebar-link"><i class="fas fa-clipboard-list"></i> My Schedule</a>
        <a href="profile.php" class="sidebar-link"><i class="fas fa-user-gear"></i> Account Settings</a>
        <div style="margin-top: 3rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
            <a href="?logout=1" class="sidebar-link" style="color: #ef4444;"><i class="fas fa-power-off"></i> Sign Out</a>
        </div>
    </aside>

    <main>
        <div style="margin-bottom: 3.5rem;">
            <h2 style="font-size: 2rem; font-family: 'Baloo Da 2'; font-weight: 700;">Dashboard Summary</h2>
            <p style="color: var(--text-muted);">Monitoring classroom availability and personal reservations.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= count($classrooms) ?></h3>
                <p>Total Room</p>
            </div>
            <div class="stat-card">
                <h3><?= count($events) ?></h3>
                <p>Your Total Bookings</p>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="font-family: 'Baloo Da 2'; font-size: 1.6rem; font-weight: 700;">Live Inventory</h2>
            <span style="color: var(--text-muted); font-size: 0.85rem;">Showing all active classrooms</span>
        </div>

        <div class="filters">
            <select id="floorFilter">
                <option value="">All Floors</option>
                <?php
                $floors = array_unique(array_column($classrooms, 'floor_num'));
                sort($floors);
                foreach ($floors as $floor) {
                    echo "<option value='$floor'>Floor $floor</option>";
                }
                ?>
            </select>
            <input type="text" id="roomSearch" placeholder="Search Room Number...">
        </div>

        <div class="room-grid">
            <?php foreach ($classrooms as $r):
                $is_available = $availability[$r['id'] ?? 0];
            ?>
                <div class="room-card" data-floor="<?= $r['floor_num'] ?>" data-room="<?= $r['room_num'] ?>">
                    <div class="room-header">
                        <span class="room-title">Room <?= htmlspecialchars($r['room_num']) ?></span>
                        <?php if ($is_available): ?>
                            <div class="status-pill active">
                                <div class="pulse-dot"></div> Available
                            </div>
                        <?php else: ?>
                            <div class="status-pill unavailable">
                                NOT AVAILABLE
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="room-body">
                        <div class="room-info-row">
                            <span>Floor Level</span>
                            <span>Level <?= $r['floor_num'] ?></span>
                        </div>
                        <div class="room-info-row">
                            <span>Max Capacity</span>
                            <span><?= $r['capacity'] ?> Persons</span>
                        </div>
                        <div class="room-info-row">
                            <span>Room Type</span>
                            <span><?= $r['type_name'] ?></span>
                        </div>
                    </div>
                    <div class="room-footer">
                        <button class="btn-reserve" data-room="<?= htmlspecialchars($r['room_num']) ?>" <?= !$is_available ? 'disabled' : '' ?>>
                            <?= $is_available ? 'Quick Reserve' : 'No Slots Available' ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="calendar-container">
            <h2 style="margin-bottom: 2.5rem; font-family: 'Baloo Da 2';">Reservation Schedule</h2>
            <div id="calendar"></div>
        </div>
    </main>

    <!-- RESERVE MODAL -->
    <div id="reserveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Quick Reserve Room</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="reserveForm">
                    <input type="hidden" name="action" value="reserve">
                    <input type="hidden" name="priority" value="<?= $auto_priority ?>">
                    <div>
                        <label for="room">Room Number</label>
                        <input type="text" id="room" name="room" readonly>
                    </div>
                    <div>
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user_name) ?>" readonly>
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_email) ?>" placeholder="your.email@example.com" required>
                    </div>
                    <div>
                        <label for="dpt">Department</label>
                        <input type="text" id="dpt" name="dpt" placeholder="Enter department name" required>
                    </div>
                    <div>
                        <label for="reason">Reason for Booking</label>
                        <textarea id="reason" name="reason" placeholder="Describe the purpose of this booking (e.g., lecture, meeting, lab session)..." required></textarea>
                    </div>
                    <div>
                        <label for="checkingid">Available Time Slot</label>
                        <select id="checkingid" name="checkingid" required>
                            <option value="">Loading available slots...</option>
                        </select>
                    </div>
                    <div>
                        <label for="urgent">Urgent Needs (Optional)</label>
                        <input type="text" id="urgent" name="urgent" placeholder="Any special requirements? (e.g., projector setup, extra seating)">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()">Cancel</button>
                <button type="submit" form="reserveForm">Confirm Reservation</button>
            </div>
        </div>
    </div>

    <!-- WAITING MODAL -->
    <div id="waitingModal" class="waiting-modal">
        <div class="waiting-modal-content">
            <i class="fas fa-clock"></i>
            <h4>Booking Pending Approval</h4>
            <div class="countdown" id="countdown">30</div>
            <p>Please wait 30 seconds for your booking to be processed. Faculty requests may affect availability.</p>
        </div>
    </div>

    <!-- SUCCESS MODAL -->
    <div id="successModal" class="success-modal">
        <div class="success-modal-content">
            <i class="fas fa-check-circle"></i>
            <h4>Reservation Approved!</h4>
            <p>Your booking has been successfully approved.</p>
            <button onclick="closeSuccessModal()">OK</button>
        </div>
    </div>

    <!-- ERROR MODAL -->
    <div id="errorModal" class="error-modal">
        <div class="error-modal-content">
            <i class="fas fa-exclamation-triangle"></i>
            <h4>Error</h4>
            <p id="errorMessage">An error occurred.</p>
            <button onclick="closeErrorModal()">OK</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
        let bookingInterval;
        let bookingTimeout;

        document.addEventListener("DOMContentLoaded", () => {
            // Initialize Calendar
            const calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {
                initialView: "dayGridMonth",
                events: <?= json_encode($events) ?>,
                height: "auto",
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                eventMouseEnter: function(info) {
                    let tooltip = document.createElement('div');
                    tooltip.className = 'tooltip';
                    tooltip.innerHTML = `
                        <strong>Room ${info.event.extendedProps.room} (Floor ${info.event.extendedProps.floor})</strong><br>
                        Reason: ${info.event.extendedProps.reason}<br>
                        Time: ${info.event.start.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit', hour12: true})} - ${info.event.end ? info.event.end.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit', hour12: true}) : ''}
                    `;
                    document.body.appendChild(tooltip);
                    tooltip.style.left = (info.jsEvent.pageX + 10) + 'px';
                    tooltip.style.top = (info.jsEvent.pageY + 10) + 'px';
                },
                eventMouseLeave: function() {
                    document.querySelectorAll('.tooltip').forEach(el => el.remove());
                }
            });
            calendar.render();

            // Handle Sidebar Toggle
            const menuToggle = document.getElementById("menuToggle");
            const sidebar = document.getElementById("sidebar");
            menuToggle.onclick = () => {
                sidebar.classList.toggle("active");
                menuToggle.classList.toggle("fa-bars-staggered");
                menuToggle.classList.toggle("fa-xmark");
            }

            // Close sidebar when clicking outside
            document.addEventListener('click', (e) => {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove("active");
                    menuToggle.classList.add("fa-bars-staggered");
                    menuToggle.classList.remove("fa-xmark");
                }
            });

            // Notification Dropdown Toggle
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationDropdown = document.getElementById('notificationDropdown');
            notificationBtn.onclick = (e) => {
                e.stopPropagation();
                notificationDropdown.style.display = notificationDropdown.style.display === 'block' ? 'none' : 'block';
            };
            document.addEventListener('click', (e) => {
                if (!notificationBtn.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.style.display = 'none';
                }
            });

            // Room Filtering
            const roomCards = document.querySelectorAll('.room-card');
            const floorFilter = document.getElementById('floorFilter');
            const roomSearch = document.getElementById('roomSearch');

            function filterRooms() {
                const selectedFloor = floorFilter.value;
                const searchTerm = roomSearch.value.toLowerCase();

                roomCards.forEach(card => {
                    const floor = card.dataset.floor;
                    const room = card.dataset.room.toLowerCase();

                    const matchesFloor = !selectedFloor || floor === selectedFloor;
                    const matchesSearch = !searchTerm || room.includes(searchTerm);

                    card.style.display = (matchesFloor && matchesSearch) ? 'block' : 'none';
                });
            }

            floorFilter.addEventListener('change', filterRooms);
            roomSearch.addEventListener('input', filterRooms);

            // Quick Reserve Modal
            const modal = document.getElementById('reserveModal');
            const form = document.getElementById('reserveForm');
            const slotSelect = document.getElementById('checkingid');
            const roomInput = document.getElementById('room');

            document.querySelectorAll('.btn-reserve').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    if (e.target.disabled) return;
                    const roomNum = e.target.dataset.room;
                    roomInput.value = roomNum;
                    slotSelect.innerHTML = '<option value="">Loading available slots...</option>';

                    try {
                        const response = await fetch(`?get_slots=1&room=${encodeURIComponent(roomNum)}`);
                        const slots = await response.json();

                        if (slots.length === 0) {
                            document.getElementById('errorMessage').textContent = 'No available time slots for this room. Please try another room or time.';
                            showErrorModal();
                            return;
                        }

                        slotSelect.innerHTML = '<option value="">Select a time slot</option>' +
                            slots.map(slot => `<option value="${slot.id}">${slot.time}</option>`).join('');
                    } catch (error) {
                        console.error('Error fetching slots:', error);
                        slotSelect.innerHTML = '<option value="">Error loading slots</option>';
                        document.getElementById('errorMessage').textContent = 'Error loading available slots. Please try again.';
                        showErrorModal();
                    }

                    modal.style.display = 'flex';
                });
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const priority = document.querySelector('input[name="priority"]').value;

                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        closeModal();

                        if (parseInt(priority) === 1) {
                            // Immediate approve for high priority
                            const approveFormData = new FormData();
                            approveFormData.append('action', 'approve');
                            approveFormData.append('booking_id', data.booking_id);
                            const approveResponse = await fetch('', {
                                method: 'POST',
                                body: approveFormData
                            });
                            const approveData = await approveResponse.json();

                            // Add approved notification
                            const list = document.getElementById('notificationList');
                            const newNotification = `
                                <div class="notification-item">
                                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                    <div class="notification-item-content">
                                        <p>New Reservation - Approved</p>
                                        <small>Just now | Priority: High</small>
                                    </div>
                                </div>
                            `;
                            if (list.classList.contains('notification-empty')) {
                                list.classList.remove('notification-empty');
                                list.innerHTML = newNotification;
                            } else {
                                list.insertAdjacentHTML('afterbegin', newNotification);
                            }

                            // Update badge count
                            let count = parseInt(document.getElementById('notificationCount').textContent) || 0;
                            count++;
                            const badge = document.getElementById('notificationCount');
                            badge.textContent = count;
                            if (count > 0) {
                                badge.classList.add('show');
                            }

                            // Show dropdown
                            notificationDropdown.style.display = 'block';

                            if (approveData.success) {
                                showSuccessModal();
                            } else {
                                document.getElementById('errorMessage').textContent = approveData.error || 'Booking could not be approved.';
                                showErrorModal();
                            }

                            setTimeout(() => location.reload(), 2000); // Reload after 2s
                        } else {
                            // Waiting for lower priority
                            showWaitingModal(data.booking_id);

                            // Add pending notification to dropdown
                            const list = document.getElementById('notificationList');
                            const newNotification = `
                                <div class="notification-item">
                                    <i class="fas fa-clock" style="color: #f59e0b;"></i>
                                    <div class="notification-item-content">
                                        <p>Room Booking - Pending</p>
                                        <small>Just now | Priority: ${priority == 2 ? 'Medium' : 'Low'}</small>
                                    </div>
                                </div>
                            `;
                            if (list.classList.contains('notification-empty')) {
                                list.classList.remove('notification-empty');
                                list.innerHTML = newNotification;
                            } else {
                                list.insertAdjacentHTML('afterbegin', newNotification);
                            }

                            // Update badge count
                            let count = parseInt(document.getElementById('notificationCount').textContent) || 0;
                            count++;
                            const badge = document.getElementById('notificationCount');
                            badge.textContent = count;
                            if (count > 0) {
                                badge.classList.add('show');
                            }

                            // Show dropdown
                            notificationDropdown.style.display = 'block';

                            setTimeout(() => location.reload(), 35000); // Reload after ~35s
                        }
                    } else {
                        document.getElementById('errorMessage').textContent = data.error || 'Unknown error';
                        showErrorModal();
                    }
                } catch (error) {
                    document.getElementById('errorMessage').textContent = 'Error submitting reservation. Please try again.';
                    showErrorModal();
                }
            });
        });

        function showWaitingModal(bookingId) {
            const waitingModal = document.getElementById('waitingModal');
            const countdownEl = document.getElementById('countdown');
            let timeLeft = 30;

            countdownEl.textContent = timeLeft;
            waitingModal.style.display = 'flex';

            // Poll status every 5 seconds
            bookingInterval = setInterval(async () => {
                try {
                    const response = await fetch(`?get_status=1&booking_id=${bookingId}`);
                    const statusData = await response.json();
                    if (statusData.status === 'approved') {
                        clearInterval(bookingInterval);
                        clearTimeout(bookingTimeout);
                        closeWaitingModal();
                        showSuccessModal();
                    } else if (statusData.status === 'cancelled') {
                        clearInterval(bookingInterval);
                        clearTimeout(bookingTimeout);
                        closeWaitingModal();
                        document.getElementById('errorMessage').textContent = 'Your booking was cancelled due to a higher priority request. We apologize for the inconvenience.';
                        showErrorModal();
                    }
                } catch (error) {
                    console.error('Error checking status:', error);
                }
            }, 5000);

            // Timeout to approve after 30 sec
            bookingTimeout = setTimeout(async () => {
                clearInterval(bookingInterval);
                const approveFormData = new FormData();
                approveFormData.append('action', 'approve');
                approveFormData.append('booking_id', bookingId);
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: approveFormData
                    });
                    const approveData = await response.json();
                    closeWaitingModal();
                    if (approveData.success) {
                        showSuccessModal();
                    } else {
                        document.getElementById('errorMessage').textContent = approveData.error || 'Booking could not be approved.';
                        showErrorModal();
                    }
                } catch (error) {
                    closeWaitingModal();
                    document.getElementById('errorMessage').textContent = 'Error approving booking. Please try again.';
                    showErrorModal();
                }
            }, 30000);

            // Countdown
            const countdownInterval = setInterval(() => {
                timeLeft--;
                countdownEl.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        }

        function closeWaitingModal() {
            document.getElementById('waitingModal').style.display = 'none';
            clearInterval(bookingInterval);
            clearTimeout(bookingTimeout);
        }

        function closeModal() {
            document.getElementById('reserveModal').style.display = 'none';
            document.getElementById('reserveForm').reset();
            document.getElementById('checkingid').innerHTML = '<option value="">Loading available slots...</option>';
            document.getElementById('room').value = '';
        }

        function showSuccessModal() {
            document.getElementById('successModal').style.display = 'flex';
        }

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        function showErrorModal() {
            document.getElementById('errorModal').style.display = 'flex';
        }

        function closeErrorModal() {
            document.getElementById('errorModal').style.display = 'none';
        }

        function closeNotificationDropdown() {
            document.getElementById('notificationDropdown').style.display = 'none';
        }

        // Close modal on outside click
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('reserveModal');
            if (e.target === modal) {
                closeModal();
            }
            const waitingModal = document.getElementById('waitingModal');
            if (e.target === waitingModal) {
                // Do not close waiting modal on outside click
            }
            const successModal = document.getElementById('successModal');
            if (e.target === successModal) {
                closeSuccessModal();
            }
            const errorModal = document.getElementById('errorModal');
            if (e.target === errorModal) {
                closeErrorModal();
            }
        });
    </script>
</body>

</html>