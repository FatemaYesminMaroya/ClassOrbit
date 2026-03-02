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

/* ================= DB ================= */
$conn = new mysqli("localhost", "root", "", "classorbit");
if ($conn->connect_error) {
    die("DB Connection failed");
}

/* ================= FETCH PROFILE PIC ================= */
$user_pic = $_SESSION['profile_pic'] ?? 'assets/default.jpg';
if (!isset($_SESSION['profile_pic'])) {
    $pic_query = null;
    if ($user_role == 'Student') { $pic_query = "SELECT pic FROM Student WHERE id = ?"; } 
    elseif ($user_role == 'Club') { $pic_query = "SELECT pic FROM Club WHERE id = ?"; } 
    elseif ($user_role == 'Faculty') { $pic_query = "SELECT pic FROM Faculty WHERE id = ?"; } 
    elseif ($user_role == 'Admin') { $pic_query = "SELECT pic FROM Admin WHERE id = ?"; }

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

/* ================= HANDLE REVIEW SUBMISSION ================= */
$review_message = '';
$show_success_modal = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $booking_id = intval($_POST['booking_id']);
    $problems = trim($_POST['problems']);

    // Check if review already exists
    $check_stmt = $conn->prepare("SELECT id FROM booking_reviews WHERE booking_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows == 0) {
        // Insert new review (only problems)
        $insert_stmt = $conn->prepare("INSERT INTO booking_reviews (booking_id, user_id, problems) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iis", $booking_id, $user_id, $problems);
        if ($insert_stmt->execute()) {
            $review_message = '<p style="color: #10b981;">Feedback submitted successfully!</p>';
            $show_success_modal = true;
        } else {
            $review_message = '<p style="color: #ef4444;">Error submitting feedback. Please try again.</p>';
        }
        $insert_stmt->close();
    } else {
        $review_message = '<p style="color: #f59e0b;">You have already reviewed this booking.</p>';
    }
    $check_stmt->close();
}

/* ================= FETCH REVIEWS ================= */
$reviews = [];
$review_query = "SELECT * FROM booking_reviews WHERE user_id = ? ORDER BY created_at DESC";
$review_stmt = $conn->prepare($review_query);
$review_stmt->bind_param("i", $user_id);
$review_stmt->execute();
$review_result = $review_stmt->get_result();
while ($row = $review_result->fetch_assoc()) {
    $reviews[$row['booking_id']] = $row;
}
$review_stmt->close();

/* ================= NOTIFICATIONS → ALL USER BOOKINGS ================= */
$notifications = [];
$sql_notif = "SELECT rb.*, cr.room_num, cr.floor_num, ch.start_time as time, ch.end_time, p.description as priority_name FROM Room_Booking rb 
              JOIN Checking ch ON rb.checking_id = ch.id 
              JOIN Class_room cr ON ch.class_room_id = cr.id 
              JOIN priority p ON rb.priority_id = p.priority_id 
              WHERE rb.user_id = ? ORDER BY ch.start_time DESC";
$stmt_notif = $conn->prepare($sql_notif);
$stmt_notif->bind_param("i", $user_id);
$stmt_notif->execute();
$result_notif = $stmt_notif->get_result();
while ($row = $result_notif->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt_notif->close();

$notification_count = count($notifications);

// Separate upcoming and past bookings
$upcoming = [];
$past = [];
$current_time = time();
foreach ($notifications as $notif) {
    $booking_time = strtotime($notif['time']);
    if ($booking_time > $current_time) {
        $upcoming[] = $notif;
    } else {
        $past[] = $notif;
    }
}

// For past, sort ascending by start time
usort($past, function($a, $b) {
    return strtotime($a['time']) - strtotime($b['time']);
});

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Schedule | ClassOrbit</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* --- NAVIGATION --- */
        nav {
            position: fixed; top: 0; width: 100%; height: 75px;
            background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 5%; z-index: 1000;
        }

        .logo-section { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logoName { font-family: 'Baloo Da 2', cursive; font-size: 1.85rem; font-weight: 700; color: var(--primary); letter-spacing: -0.5px; }
        .logoName span { color: #fff; }

        .nav-actions { display: flex; align-items: center; gap: 1.5rem; }

        .notification-chip {
            position: relative;
            display: flex; align-items: center;
        }
        .notification-chip i {
            font-size: 1.4rem; color: white; cursor: pointer; transition: 0.3s;
        }
        .notification-chip i:hover {
            color: #e7e7e7ff;
        }
        .notification-badge {
            position: absolute; top: -5px; right: -8px; background: #ef4444; color: white;
            border-radius: 50%; min-width: 18px; height: 18px; font-size: 0.7rem;
            display: flex; align-items: center; justify-content: center; font-weight: bold;
            opacity: 0; transition: 0.3s;
        }
        .notification-badge.show {
            opacity: 1;
        }

        .profile-chip {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,0.03); padding: 6px 14px 6px 6px;
            border-radius: 50px; border: 1px solid var(--border);
        }
        .profile-chip img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .profile-chip span { font-size: 0.85rem; font-weight: 600; }

        .menu-btn { font-size: 1.4rem; color: var(--primary); cursor: pointer; transition: 0.3s; }

        /* --- NOTIFICATION DROPDOWN --- */
        .notification-dropdown {
            display: none;
            position: fixed;
            top: 75px;
            right: -1%;
            width: 350px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
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
    right: -320px; /* Increased slightly for shadow clearance */
    top: 0;
    height: 100%;
    width: 300px;
    background: rgba(2, 6, 23, 0.95); /* Deeper sidebar color */
    backdrop-filter: blur(15px); /* Professional glass effect */
    border-left: 1px solid rgba(51, 65, 85, 0.5);
    padding: 100px 1.2rem 2rem;
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); /* Ultra-smooth "Out" curve */
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
    overflow: hidden; /* Clips the hover glow */
}

/* Elegant Hover Effect */
.sidebar-link:hover {
    color: #fff;
    background: rgba(245, 158, 11, 0.08);
    padding-left: 28px; /* Subtle slide-in effect */
}

/* Professional Active State */
.sidebar-link.active {
    background: linear-gradient(90deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0) 100%);
    color: white;
    font-weight: 600;
    padding-left: 28px;
}

/* The Animated Accent Line */
.sidebar-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 20%;
    bottom: 20%;
    width: 3px;
    background: var(--primary);
    border-radius: 0 4px 4px 0;
    transform: scaleY(0); /* Hidden by default */
    transition: transform 0.3s cubic-bezier(0.65, 0, 0.35, 1);
}

.sidebar-link:hover::before,
.sidebar-link.active::before {
    transform: scaleY(1); /* Smoothly grows from center */
}

/* Icon Polish */
.sidebar-link i {
    font-size: 1.1rem;
    transition: transform 0.3s ease;
}

.sidebar-link:hover i {
    transform: scale(1.1);
    color: var(--primary);
}

/* Bottom Logout Section Polish */
.sidebar div[style*="margin-top"] {
    margin-top: 2rem !important;
    padding-top: 1.5rem !important;
    border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
}

        /* --- MAIN --- */
        main { padding: 120px 5% 60px; max-width: 1400px; margin: auto; }

        /* --- SCHEDULE SECTION --- */
        .schedule-container {
            background: var(--bg-card); padding: 2.5rem; border-radius: 24px;
            border: 1px solid var(--border); margin: 2rem 0;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .schedule-header {
            margin-bottom: 2rem;
        }
        .schedule-header h2 { font-family: 'Baloo Da 2'; font-size: 1.6rem; font-weight: 700; }

        .section-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border);
        }
        .section-header h3 { font-family: 'Baloo Da 2'; font-size: 1.25rem; font-weight: 600; margin: 0; }
        .section-count { color: var(--primary); font-weight: 600; font-size: 0.9rem; }

        /* --- BOOKINGS LIST --- */
        .bookings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .booking-card {
            background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border);
            padding: 1.5rem; transition: 0.3s;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .booking-card:hover { 
            border-color: var(--primary); 
            transform: translateY(-2px); 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .booking-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .booking-title { font-weight: 600; color: var(--text-main); }
        .status-badge {
            padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
        }
        .status-approved { background: rgba(22, 163, 74, 0.2); color: #16a34a; }
        .status-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .status-cancelled { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .booking-details { color: var(--text-muted); font-size: 0.9rem; line-height: 1.5; }
        .booking-details strong { color: var(--text-main); }

        .no-bookings { text-align: center; padding: 3rem; color: var(--text-muted); }

        /* --- REVIEW SECTION --- */
        .review-section { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); }
        .review-section h4 { font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-main); }
        .review-form textarea { width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-dark); color: var(--text-main); margin-bottom: 0.5rem; resize: vertical; }
        .review-form button { background: var(--primary); color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        .review-form button:hover { background: var(--primary-dark); }
        .existing-review { background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; border: 1px solid var(--border); }
        .existing-review p { margin: 0.25rem 0; }
        .existing-review small { color: var(--text-muted); }

        /* --- SUCCESS MODAL --- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        }
        .modal-icon {
            font-size: 4.5rem;
            color: #10b981;
            margin-bottom: 1rem;
        }
        .modal-title {
            font-family: 'Baloo Da 2', cursive;
            font-size: 1.9rem;
            margin-bottom: 1rem;
        }
        .modal-text {
            color: var(--text-muted);
            margin-bottom: 1.8rem;
            line-height: 1.5;
        }
        .modal-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.9rem 2.2rem;
            font-size: 1.05rem;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.25s;
        }
        .modal-btn:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .profile-chip span { display: none; }
            .section-header { flex-direction: column; gap: 0.5rem; align-items: stretch; text-align: center; }
            .notification-dropdown { right: 10px; left: 10px; width: auto; }
        }
    </style>
</head>
<body>

    <nav>
        <a href="dashboard.php" class="logo-section">
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
            <h4>Recent Activity</h4>
            <button class="notification-dropdown-close">×</button>
        </div>
        <div id="notificationList">
            <?php if (empty($notifications)): ?>
                <div class="notification-empty">
                    <p>No recent activity</p>
                </div>
            <?php else: ?>
                <?php foreach (array_slice($notifications, 0, 5) as $notif): 
                    $status_icon = $notif['status'] == 'approved' ? 'fa-check-circle' : ($notif['status'] == 'pending' ? 'fa-clock' : ($notif['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-exclamation-triangle'));
                    $status_color = $notif['status'] == 'approved' ? '#10b981' : ($notif['status'] == 'pending' ? '#f59e0b' : '#ef4444');
                    $cancel_msg = ($notif['status'] == 'cancelled') ? '<br><small style="color: #ef4444; font-style: italic;">Rescheduled due to higher priority.</small>' : '';
                ?>
                <div class="notification-item">
                    <i class="fas <?= $status_icon ?>" style="color: <?= $status_color ?>;"></i>
                    <div class="notification-item-content">
                        <p>Room <?= htmlspecialchars($notif['room_num']) ?> - <?= htmlspecialchars($notif['reason']) ?></p>
                        <small><?= date('M d, Y h:i A', strtotime($notif['time'])) ?> - <?= date('h:i A', strtotime($notif['end_time'])) ?> | <?= ucfirst($notif['status']) ?><?= $cancel_msg ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-link"><i class="fas fa-house"></i> Dashboard </a>
        <!-- <a href="book_room.php" class="sidebar-link"><i class="fas fa-calendar-plus"></i> Reserve Room</a> -->
        <a href="my_schedule.php" class="sidebar-link active"><i class="fas fa-clipboard-list"></i> My Schedule</a>
        <a href="profile.php" class="sidebar-link"><i class="fas fa-user-gear"></i> Account Settings</a>
        <div style="margin-top: 3rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
            <a href="?logout=1" class="sidebar-link" style="color: #ef4444;"><i class="fas fa-power-off"></i> Sign Out</a>
        </div>
    </aside>

    <main>
        <div style="margin-bottom: 3.5rem;">
            <h2 style="font-size: 2rem; font-family: 'Baloo Da 2'; font-weight: 700;">My Reservations</h2>
            <p style="color: var(--text-muted);">View and manage your room reservations.</p>
            <?= $review_message ?>
        </div>

        <div class="schedule-container">
            <div class="schedule-header">
                <h2>Your Upcoming Reservations</h2>
            </div>

            <?php if (empty($upcoming)): ?>
                <div class="no-bookings">
                    <i class="fas fa-calendar-plus" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                    <p>No upcoming reservations. <a href="book_room.php" style="color: var(--primary);">Book now!</a></p>
                </div>
            <?php else: ?>
                <div class="section-header">
                    <h3>Upcoming</h3>
                    <span class="section-count"><?= count($upcoming) ?> reservation<?= count($upcoming) > 1 ? 's' : '' ?></span>
                </div>
                <div class="bookings-grid">
                    <?php foreach ($upcoming as $booking): 
                        $status_class = "status-{$booking['status']}";
                        $status_icon = $booking['status'] == 'approved' ? 'fa-check-circle' : ($booking['status'] == 'pending' ? 'fa-clock' : 'fa-times-circle');
                        $status_color = $booking['status'] == 'approved' ? '#10b981' : ($booking['status'] == 'pending' ? '#f59e0b' : '#ef4444');
                    ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-title">
                                <i class="fas fa-door-open" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                Room <?= htmlspecialchars($booking['room_num']) ?> (Floor <?= $booking['floor_num'] ?? 'N/A' ?>)
                            </div>
                            <span class="status-badge <?= $status_class ?>">
                                <i class="fas <?= $status_icon ?>" style="color: <?= $status_color ?>; margin-right: 0.25rem;"></i>
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </div>
                        <div class="booking-details">
                            <p><strong>Date & Time:</strong> <?= date('M d, Y h:i A', strtotime($booking['time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?></p>
                            <p><strong>Reason:</strong> <?= htmlspecialchars($booking['reason']) ?></p>
                            <?php if (!empty($booking['urgent_needs'])): ?>
                                <p><strong>Special Needs:</strong> <?= htmlspecialchars($booking['urgent_needs']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="schedule-header" style="margin-top: 3rem;">
                <h2>Past Reservations</h2>
            </div>

            <?php if (empty($past)): ?>
                <div class="no-bookings">
                    <i class="fas fa-history" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                    <p>No past reservations yet.</p>
                </div>
            <?php else: ?>
                <div class="section-header">
                    <h3>Past</h3>
                    <span class="section-count"><?= count($past) ?> reservation<?= count($past) > 1 ? 's' : '' ?></span>
                </div>
                <div class="bookings-grid">
                    <?php foreach ($past as $booking): 
                        $status_class = "status-{$booking['status']}";
                        $status_icon = $booking['status'] == 'approved' ? 'fa-check-circle' : ($booking['status'] == 'pending' ? 'fa-clock' : 'fa-times-circle');
                        $status_color = $booking['status'] == 'approved' ? '#10b981' : ($booking['status'] == 'pending' ? '#f59e0b' : '#ef4444');
                        $has_review = isset($reviews[$booking['id']]);
                        $review = $has_review ? $reviews[$booking['id']] : null;
                    ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-title">
                                <i class="fas fa-door-open" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                Room <?= htmlspecialchars($booking['room_num']) ?> (Floor <?= $booking['floor_num'] ?? 'N/A' ?>)
                            </div>
                            <span class="status-badge <?= $status_class ?>">
                                <i class="fas <?= $status_icon ?>" style="color: <?= $status_color ?>; margin-right: 0.25rem;"></i>
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </div>
                        <div class="booking-details">
                            <p><strong>Date & Time:</strong> <?= date('M d, Y h:i A', strtotime($booking['time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?></p>
                            <p><strong>Reason:</strong> <?= htmlspecialchars($booking['reason']) ?></p>
                            <?php if (!empty($booking['urgent_needs'])): ?>
                                <p><strong>Special Needs:</strong> <?= htmlspecialchars($booking['urgent_needs']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="review-section">
                            <h4>Feedback / Issues</h4>
                            <?php if ($has_review): ?>
                                <div class="existing-review">
                                    <?php if (!empty($review['problems'])): ?>
                                        <p><strong>Problems / Feedback:</strong> <?= htmlspecialchars($review['problems']) ?></p>
                                    <?php else: ?>
                                        <p style="font-style: italic; color: var(--text-muted);">No specific issues reported.</p>
                                    <?php endif; ?>
                                    <small>Submitted on <?= date('M d, Y h:i A', strtotime($review['created_at'])) ?></small>
                                </div>
                            <?php else: ?>
                                <form class="review-form" method="POST">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <textarea name="problems" placeholder="Any problems or feedback about this booking? (optional)"></textarea>
                                    <button type="submit" name="submit_review">Submit Feedback</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ================= SUCCESS MODAL ================= -->
    <div id="successModal" class="modal-overlay" style="display: <?= $show_success_modal ? 'flex' : 'none' ?>;">
        <div class="modal-content">
            <i class="fas fa-check-circle modal-icon"></i>
            <h3 class="modal-title">Thank You!</h3>
            <p class="modal-text">Your feedback has been successfully submitted.<br>We appreciate your input.</p>
            <button class="modal-btn" id="closeSuccessModal">Close</button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
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
            const closeBtn = document.querySelector('.notification-dropdown-close');
            notificationBtn.onclick = (e) => {
                e.stopPropagation();
                notificationDropdown.style.display = notificationDropdown.style.display === 'block' ? 'none' : 'block';
            };
            closeBtn.onclick = () => {
                notificationDropdown.style.display = 'none';
            };
            document.addEventListener('click', (e) => {
                if (!notificationBtn.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.style.display = 'none';
                }
            });

            // Success Modal Close
            const successModal = document.getElementById('successModal');
            const closeModalBtn = document.getElementById('closeSuccessModal');

            if (successModal) {
                closeModalBtn?.addEventListener('click', () => {
                    successModal.style.display = 'none';
                });

                successModal.addEventListener('click', (e) => {
                    if (e.target === successModal) {
                        successModal.style.display = 'none';
                    }
                });

                // Optional: close with Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && successModal.style.display === 'flex') {
                        successModal.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>