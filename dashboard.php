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
} elseif ($user_role == 'Admin') {
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

/* ================= NOTIFICATIONS → USER BOOKINGS ================= */
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
    $status_icon = $row['status'] == 'approved' ? 'fa-check-circle' : ($row['status'] == 'pending' ? 'fa-clock' : ($row['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-exclamation-triangle'));
    $status_color = $row['status'] == 'approved' ? '#10b981' : ($row['status'] == 'pending' ? '#f59e0b' : '#ef4444');

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

/* ================= NEW: FETCH REPORTED PROBLEMS PER ROOM ================= */
$problems_by_room = [];
$prob_stmt = $conn->prepare("
    SELECT cr.room_num, br.problems
    FROM booking_reviews br
    JOIN room_booking rb ON br.booking_id = rb.id
    JOIN checking ch ON rb.checking_id = ch.id
    JOIN class_room cr ON ch.class_room_id = cr.id
    WHERE br.problems IS NOT NULL 
      AND br.problems != ''
    ORDER BY cr.room_num, br.created_at DESC
");
$prob_stmt->execute();
$prob_result = $prob_stmt->get_result();

while ($prob_row = $prob_result->fetch_assoc()) {
    $room_num = $prob_row['room_num'];
    if (!isset($problems_by_room[$room_num])) {
        $problems_by_room[$room_num] = [];
    }
    $problems_by_room[$room_num][] = $prob_row['problems'];
}
$prob_stmt->close();

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

    <style>
        :root {
            --primary: #f59e0b;
            --primary-dark: #d97706;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-sidebar: #020617;
            --border: #334155;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* --- NAVIGATION --- */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            height: 75px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 5%;
            z-index: 1000;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logoName {
            font-family: 'Baloo Da 2', cursive;
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .logoName span {
            color: #fff;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .notification-chip {
            position: relative;
            display: flex;
            align-items: center;
        }

        .notification-chip i {
            font-size: 1.4rem;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }

        .notification-chip i:hover {
            color: #e7e7e7ff;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            opacity: 0;
            transition: 0.3s;
        }

        .notification-badge.show {
            opacity: 1;
        }

        .profile-chip {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.03);
            padding: 6px 14px 6px 6px;
            border-radius: 50px;
            border: 1px solid var(--border);
        }

        .profile-chip img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .profile-chip span {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .menu-btn {
            font-size: 1.4rem;
            color: var(--primary);
            cursor: pointer;
            transition: 0.3s;
        }

        /* --- NOTIFICATION DROPDOWN --- */
        .notification-dropdown {
            display: none;
            position: fixed;
            top: 75px;
            right: 3%;
            width: 350px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-dropdown h4 {
            margin: 0;
            color: var(--text-main);
            font-family: 'Baloo Da 2';
            font-size: 1.1rem;
        }

        .notification-dropdown-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            transition: 0.3s;
        }

        .notification-dropdown-close:hover {
            background: rgba(245, 158, 11, 0.1);
            color: var(--primary);
        }

        .notification-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            transition: 0.3s;
        }

        .notification-item:hover {
            background: rgba(245, 158, 11, 0.05);
        }

        .notification-item i {
            color: #10b981;
            margin-top: 0.25rem;
            flex-shrink: 0;
        }

        .notification-item-content p {
            margin: 0 0 0.25rem 0;
            font-weight: 500;
            color: var(--text-main);
        }

        .notification-item-content small {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .notification-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--text-muted);
        }

        /* --- HIGH-CLASS SIDEBAR --- */
        .sidebar {
            position: fixed;
            right: -320px;
            top: 0;
            height: 100%;
            width: 300px;
            background: rgba(2, 6, 23, 0.95);
            backdrop-filter: blur(15px);
            border-left: 1px solid rgba(51, 65, 85, 0.5);
            padding: 100px 1.2rem 2rem;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 999;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.5);
        }

        .sidebar.active {
            right: 0;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 20px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .sidebar-link:hover {
            color: #fff;
            background: rgba(245, 158, 11, 0.08);
            padding-left: 28px;
        }

        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0) 100%);
            color: white;
            font-weight: 600;
            padding-left: 28px;
        }

        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 20%;
            bottom: 20%;
            width: 3px;
            background: var(--primary);
            border-radius: 0 4px 4px 0;
            transform: scaleY(0);
            transition: transform 0.3s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .sidebar-link:hover::before,
        .sidebar-link.active::before {
            transform: scaleY(1);
        }

        .sidebar-link i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .sidebar-link:hover i {
            transform: scale(1.1);
            color: var(--primary);
        }

        .sidebar div[style*="margin-top"] {
            margin-top: 2rem !important;
            padding-top: 1.5rem !important;
            border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
        }

        /* --- MAIN --- */
        main {
            padding: 120px 5% 60px;
            max-width: 1400px;
            margin: auto;
        }

        /* --- STATS (HIDDEN PRIORITY) --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--border);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .stat-card h3 {
            font-size: 2.8rem;
            color: var(--primary);
            font-family: 'Baloo Da 2';
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        /* --- FILTERS --- */
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters select,
        .filters input {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.95rem;
            min-width: 150px;
        }

        .filters input {
            min-width: 200px;
        }

        .filters select:focus,
        .filters input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .filters select option {
            background: var(--bg-card);
            color: var(--text-main);
        }

        /* --- PROFESSIONAL ROOM GRID --- */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .room-card {
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .room-card:hover {
            border-color: var(--primary);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .room-header {
            background: rgba(255, 255, 255, 0.02);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }

        .room-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
        }

        .status-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.65rem;
            padding: 4px 10px;
            border-radius: 50px;
            font-weight: 800;
        }

        .status-pill.active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-pill.unavailable {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
            animation: pulse 2s infinite;
        }

        /* NEW: Room Problems Display */
        .room-problems {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 12px;
            margin: 1rem 1.5rem;
            padding: 1rem;
            color: #ef4444;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .room-problems h5 {
            margin: 0 0 0.6rem;
            font-size: 1rem;
            font-weight: 600;
            color: #ef4444;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .room-problems ul {
            margin: 0;
            padding-left: 1.5rem;
            list-style: none;
        }

        .room-problems li {
            position: relative;
            margin-bottom: 0.5rem;
        }

        .room-problems li::before {
            content: "•";
            position: absolute;
            left: -1.2rem;
            color: #ef4444;
        }

        .room-body {
            padding: 1.5rem;
        }

        .room-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        .room-info-row span:first-child {
            color: var(--text-muted);
            font-weight: 400;
        }

        .room-info-row span:last-child {
            color: #fff;
            font-weight: 600;
        }

        .room-footer {
            padding: 0 1.5rem 1.5rem;
        }

        .btn-reserve {
            display: block;
            width: 100%;
            text-align: center;
            background: rgba(245, 158, 11, 0.08);
            color: var(--primary);
            padding: 10px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid rgba(245, 158, 11, 0.2);
            transition: 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-reserve:hover {
            background: var(--primary);
            color: #000;
            border-color: var(--primary);
        }

        .btn-reserve:disabled {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.2);
            cursor: not-allowed;
        }

        .btn-reserve:disabled:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.2);
            transform: none;
        }

        /* --- MODAL --- */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1001;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-content {
            background: var(--bg-card);
            padding: 0;
            border-radius: 20px;
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border);
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(245, 158, 11, 0.05);
        }

        .modal h3 {
            margin: 0;
            color: var(--text-main);
            font-family: 'Baloo Da 2';
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: 0.3s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(245, 158, 11, 0.1);
            color: var(--primary);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-main);
            font-weight: 500;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .modal input,
        .modal select,
        .modal textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            background: #1e293b;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-main);
            margin-bottom: 1.5rem;
            font-size: 1rem;
            transition: 0.3s;
        }

        .modal input::placeholder,
        .modal textarea::placeholder {
            color: var(--text-muted);
            opacity: 1;
        }

        .modal select option {
            background: #1e293b;
            color: var(--text-main);
        }

        .modal input:focus,
        .modal select:focus,
        .modal textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(30, 41, 59, 0.8);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .modal textarea {
            resize: vertical;
            min-height: 100px;
            font-family: 'Inter', sans-serif;
        }

        .modal-footer {
            padding: 1.5rem 2rem 2rem;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .modal button[type="submit"],
        .modal button[type="button"] {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: 0.3s;
            flex: 1;
            max-width: 150px;
        }

        .modal button[type="submit"] {
            background: var(--primary-dark);
            color: #ffffffff;
        }

        .modal button[type="submit"]:hover {
            background: var(--primary);
            transform: translateY(-1px);
        }

        .modal button[type="button"] {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--border);
        }

        .modal button[type="button"]:hover {
            background: rgba(245, 158, 11, 0.1);
            border-color: var(--primary);
            color: var(--primary);
        }

        /* --- WAITING MODAL --- */
        .waiting-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1002;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .waiting-modal-content {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border);
        }

        .waiting-modal i {
            font-size: 3rem;
            color: #f59e0b;
            margin-bottom: 1rem;
        }

        .waiting-modal h4 {
            color: var(--text-main);
            margin-bottom: 1rem;
            font-family: 'Baloo Da 2';
        }

        .waiting-modal p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .countdown {
            font-size: 2rem;
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 1rem;
        }

        /* --- SUCCESS MODAL --- */
        .success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1002;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .success-modal-content {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border);
        }

        .success-modal i {
            font-size: 3rem;
            color: #10b981;
            margin-bottom: 1rem;
        }

        .success-modal h4 {
            color: var(--text-main);
            margin-bottom: 1rem;
            font-family: 'Baloo Da 2';
        }

        .success-modal p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .success-modal button {
            padding: 0.875rem 2rem;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .success-modal button:hover {
            background: var(--primary-dark);
        }

        /* --- ERROR MODAL --- */
        .error-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1002;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .error-modal-content {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border);
        }

        .error-modal i {
            font-size: 3rem;
            color: #ef4444;
            margin-bottom: 1rem;
        }

        .error-modal h4 {
            color: var(--text-main);
            margin-bottom: 1rem;
            font-family: 'Baloo Da 2';
        }

        .error-modal p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .error-modal button {
            padding: 0.875rem 2rem;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .error-modal button:hover {
            background: var(--primary-dark);
        }

        /* --- CALENDAR TOOLTIP --- */
        .tooltip {
            position: absolute;
            background: var(--bg-card);
            color: var(--text-main);
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
            z-index: 1000;
            pointer-events: none;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            white-space: nowrap;
            max-width: 250px;
            word-wrap: break-word;
        }

        /* --- CALENDAR --- */
        .calendar-container {
            background: var(--bg-card);
            padding: 2.5rem;
            border-radius: 24px;
            border: 1px solid var(--border);
            margin: 5rem 0;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        @media (max-width: 768px) {
            .profile-chip span {
                display: none;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                margin: 1rem;
            }

            .modal-footer {
                flex-direction: column-reverse;
            }

            .modal button[type="submit"],
            .modal button[type="button"] {
                max-width: none;
            }

            .notification-dropdown {
                right: 10px;
                left: 10px;
                width: auto;
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filters select,
            .filters input {
                min-width: auto;
            }
        }
    </style>
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
                $room_num      = $r['room_num'];
                $is_available  = $availability[$r['id']] ?? false;
                $has_problems  = !empty($problems_by_room[$room_num]);
                $problems_list = $has_problems ? $problems_by_room[$room_num] : [];
            ?>
                <div class="room-card" data-floor="<?= $r['floor_num'] ?>" data-room="<?= $room_num ?>">
                    <div class="room-header">
                        <span class="room-title">Room <?= htmlspecialchars($room_num) ?></span>
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

                    <!-- === NEW: Show reported problems (only if exist) === -->
                    <?php if ($has_problems): ?>
                        <div class="room-problems">
                            <h5><i class="fas fa-exclamation-triangle"></i> Reported Issues:</h5>
                            <ul>
                                <?php foreach ($problems_list as $problem): ?>
                                    <li><?= htmlspecialchars($problem) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

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
                        <button class="btn-reserve" data-room="<?= htmlspecialchars($room_num) ?>" <?= !$is_available ? 'disabled' : '' ?>>
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