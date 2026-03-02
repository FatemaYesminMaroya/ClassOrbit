<?php
session_start();

/* ================= LOGOUT HANDLING ================= */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$user_name  = $_SESSION['user_name'] ?? 'System Administrator';
$user_email = $_SESSION['user_email'] ?? 'admin@classorbit.com';

/* ================= DB CONNECTION ================= */
$conn = new mysqli("localhost", "root", "", "classorbit");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ================= PROCESS FORMS ================= */
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ===== Cancel Booking ===== */
    if (isset($_POST['cancel_booking'])) {
        $booking_id    = (int) $_POST['booking_id'];
        $cancel_reason = trim($_POST['cancel_reason']);

        if (!empty($cancel_reason)) {
            $stmt = $conn->prepare("
                UPDATE room_booking
                SET status = 'cancelled', cancel_reason = ?
                WHERE id = ?
            ");
            $stmt->bind_param("si", $cancel_reason, $booking_id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $msg = "
                <div class='alert success'>
                    <i class='fas fa-check-circle'></i>
                    Booking cancelled successfully!<br>
                    <strong>Reason:</strong> " . htmlspecialchars($cancel_reason) . "
                </div>";
            } else {
                $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Failed to cancel booking.</div>";
            }
            $stmt->close();
        }
    }

    /* ===== Add Room ===== */
    elseif (isset($_POST['add_room'])) {
        $room_num  = (int) $_POST['room_num'];
        $floor_num = (int) $_POST['floor_num'];
        $capacity  = (int) $_POST['capacity'];
        $type_name = $_POST['type_name'];

        $projector = isset($_POST['projector']) ? 'Yes' : 'No';
        $ac        = isset($_POST['ac']) ? 'Yes' : 'No';
        $speaker   = isset($_POST['speaker']) ? 'Yes' : 'No';
        $any_prob  = trim($_POST['any_prob']);

        $check = $conn->prepare("SELECT id FROM class_room WHERE room_num = ?");
        $check->bind_param("i", $room_num);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Room number already exists!</div>";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO class_room
                (room_num, floor_num, capacity, type_name, projector, AC, speaker, any_prob)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiisssss",
                $room_num,
                $floor_num,
                $capacity,
                $type_name,
                $projector,
                $ac,
                $speaker,
                $any_prob
            );

            if ($stmt->execute()) {
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> New room added successfully!</div>";
            } else {
                $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error adding room.</div>";
            }
            $stmt->close();
        }
        $check->close();
    }

    /* ===== NEW: Mark Problem as Resolved ===== */
    elseif (isset($_POST['resolve_problem'])) {
        $review_id = (int)$_POST['review_id'];

        $stmt = $conn->prepare("UPDATE booking_reviews SET problems = NULL WHERE id = ?");
        $stmt->bind_param("i", $review_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Issue marked as resolved successfully!</div>";
        } else {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Failed to resolve issue.</div>";
        }
        $stmt->close();
    }
}

/* ================= DASHBOARD STATS ================= */
$total_rooms = $conn->query("SELECT COUNT(*) FROM class_room")->fetch_row()[0] ?? 0;
$pending_bookings = $conn->query("SELECT COUNT(*) FROM room_booking WHERE status='pending'")->fetch_row()[0] ?? 0;

// FIXED: Correct conflict detection query
$conflicts = $conn->query("
    SELECT COUNT(*) as conflict_count FROM (
        SELECT DISTINCT 
            LEAST(rb1.id, rb2.id) as b1,
            GREATEST(rb1.id, rb2.id) as b2
        FROM room_booking rb1
        JOIN room_booking rb2 ON rb1.id < rb2.id
        JOIN checking c1 ON rb1.checking_id = c1.id
        JOIN checking c2 ON rb2.checking_id = c2.id
        WHERE c1.class_room_id = c2.class_room_id
            AND rb1.status = 'approved'
            AND rb2.status = 'approved'
            AND (
                (c1.start_time < c2.end_time AND c1.end_time > c2.start_time)
            )
    ) as conflicts
")->fetch_assoc()['conflict_count'] ?? 0;

$total_users = 0;
$total_users += $conn->query("SELECT COUNT(*) FROM student")->fetch_row()[0];
$total_users += $conn->query("SELECT COUNT(*) FROM faculty")->fetch_row()[0];
$total_users += $conn->query("SELECT COUNT(*) FROM club")->fetch_row()[0];

/* ================= RECENT BOOKINGS ================= */
$sql = "
SELECT
    rb.id, rb.reason, rb.status, rb.created_at,
    rb.urgent_needs, rb.cancel_reason, rb.dept,
    cr.room_num,
    c.start_time, c.end_time,
    CASE
        WHEN rb.priority_id = 1 THEN 'faculty'
        WHEN rb.priority_id = 2 THEN 'club'
        WHEN rb.priority_id = 3 THEN 'student'
    END AS user_type,
    COALESCE(f.name, s.name, cl.name) AS user_name
FROM room_booking rb
JOIN checking c ON rb.checking_id = c.id
JOIN class_room cr ON c.class_room_id = cr.id
LEFT JOIN faculty f ON rb.user_id = f.id AND rb.priority_id = 1
LEFT JOIN club cl   ON rb.user_id = cl.id AND rb.priority_id = 2
LEFT JOIN student s ON rb.user_id = s.id AND rb.priority_id = 3
WHERE rb.status IN ('pending', 'approved', 'cancelled')
ORDER BY rb.created_at DESC
LIMIT 8
";
$recent_bookings = $conn->query($sql);

/* ================= NEW: REPORTED ROOM PROBLEMS ================= */
$problems_query = "
    SELECT 
        br.id AS review_id,
        cr.room_num,
        br.problems,
        br.created_at,
        COALESCE(s.name, f.name, cl.name) AS reported_by,
        CASE 
            WHEN br.user_id IN (SELECT id FROM student) THEN 'Student'
            WHEN br.user_id IN (SELECT id FROM faculty) THEN 'Faculty'
            WHEN br.user_id IN (SELECT id FROM club) THEN 'Club'
        END AS user_type
    FROM booking_reviews br
    JOIN room_booking rb ON br.booking_id = rb.id
    JOIN checking ch ON rb.checking_id = ch.id
    JOIN class_room cr ON ch.class_room_id = cr.id
    LEFT JOIN student s ON br.user_id = s.id
    LEFT JOIN faculty f ON br.user_id = f.id
    LEFT JOIN club cl ON br.user_id = cl.id
    WHERE br.problems IS NOT NULL AND br.problems != ''
    ORDER BY br.created_at DESC
";
$problems_result = $conn->query($problems_query);

/* ================= ROOM TYPES ================= */
$room_types = $conn->query("SELECT type_name FROM type");
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ClassOrbit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Baloo Da 2', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }

        nav {
            background-color: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 3rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logoName {
            color: #f59e0b;
            font-size: 1.75rem;
            font-weight: bold;
        }

        .orbitclass {
            color: #ffffff;
        }

        .hamburger {
            display: none;
            font-size: 1.8rem;
            color: #f59e0b;
            cursor: pointer;
            background: none;
            border: none;
        }

        aside.sidebar {
            width: 280px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            position: fixed;
            left: 0;
            top: 80px;
            bottom: 0;
            padding: 1.5rem;
            border-right: 1px solid #334155;
            overflow-y: auto;
            z-index: 999;
            transition: transform 0.4s ease;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .sidebar ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        main {
            margin-left: 280px;
            padding: 120px 3rem 3rem;
            width: calc(100% - 280px);
            transition: all 0.4s ease;
        }

        main.full {
            margin-left: 0;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.2rem;
            color: #f8fafc;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.3rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert.success {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
            color: #d4edda;
        }

        .alert.error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #f8d7da;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: #1e293b;
            padding: 1.8rem;
            border-radius: 12px;
            border: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-info p {
            color: #94a3b8;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .icon-pending {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .icon-rooms {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        .icon-conflicts {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }

        .icon-users {
            background: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
        }

        .section-card {
            background: #1e293b;
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid #334155;
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #334155;
        }

        .section-title {
            font-size: 1.8rem;
            color: #f8fafc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #334155;
        }

        th {
            color: #94a3b8;
            font-weight: 600;
        }

        tr:hover {
            background: rgba(245, 158, 11, 0.05);
        }

        .status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: rgba(255, 243, 205, 0.2);
            color: #f39c12;
        }

        .status-approved {
            background: rgba(209, 236, 241, 0.2);
            color: #27ae60;
        }

        .status-cancelled,
        .status-rejected {
            background: rgba(248, 215, 218, 0.2);
            color: #e74c3c;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-faculty {
            background: rgba(142, 68, 173, 0.2);
            color: #8e44ad;
        }

        .badge-club {
            background: rgba(230, 126, 34, 0.2);
            color: #e67e22;
        }

        .badge-student {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: opacity 0.25s ease;
            text-decoration: none;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-primary {
            background: linear-gradient(145deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-success {
            background: linear-gradient(145deg, #27ae60, #1e8449);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(145deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(145deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-view {
            background: linear-gradient(145deg, #17a2b8, #138496);
            color: white;
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }

        .btn-cancel {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.05rem;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .quick-actions {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 2.5rem;
            justify-content: center;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        .modal-content {
            background: #1e293b;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem;
            border: 1px solid #334155;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            background: none;
            border: none;
            font-size: 2rem;
            color: #94a3b8;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .close-modal:hover {
            opacity: 1;
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #334155;
        }

        .modal-header i {
            font-size: 2rem;
            color: #f59e0b;
        }

        .modal-header h2 {
            font-size: 1.8rem;
            color: #f8fafc;
            margin: 0;
        }

        /* Grid for 2 columns */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #cbd5e1;
        }

        .form-label span {
            color: #e74c3c;
        }

        .form-control {
            padding: 0.9rem 1rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #f1f5f9;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 0.8rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #cbd5e1;
        }

        .checkbox-item input[type="checkbox"] {
            accent-color: #f59e0b;
            width: 18px;
            height: 18px;
        }

        @media (max-width: 992px) {
            .hamburger {
                display: block;
            }

            aside.sidebar {
                transform: translateX(-100%);
            }

            aside.sidebar.visible {
                transform: translateX(0);
            }

            main {
                margin-left: 0;
                width: 100%;
                padding-top: 140px;
            }

            .nav-container {
                padding: 0 1.5rem;
            }

            .quick-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-container">
            <div class="logo-section">
                <i class="fas fa-graduation-cap" style="font-size: 1.75rem;"></i>
                <span class="logoName">Class<span class="orbitclass">Orbit</span></span>
            </div>
            <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
        </div>
    </nav>
    <aside class="sidebar" id="sidebar">
        <ul>
            <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_manage_bookings.php"><i class="fas fa-calendar-check"></i> Manage Bookings</a></li>
            <li><a href="admin_manage_rooms.php"><i class="fas fa-door-closed"></i> Manage Rooms</a></li>
            <li><a href="admin_conflicts_manage.php"><i class="fas fa-exclamation-triangle"></i> Conflicts</a></li>
            <li><a href="admin_system_settings.php"><i class="fas fa-cogs"></i> System Settings</a></li>
            <li class="logout"><a href="?logout=1"><i class="fas fa-power-off"></i> Logout</a></li>
        </ul>
    </aside>

    <main id="mainContent">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
                <div>
                    <p style="font-weight:600;"><?= htmlspecialchars($user_name) ?></p>
                    <p style="font-size:0.9rem;color:#94a3b8;"><?= htmlspecialchars($user_email) ?></p>
                </div>
            </div>
        </div>
        <?= $msg ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $pending_bookings ?></h3>
                    <p>Pending Bookings</p>
                </div>
                <div class="stat-icon icon-pending"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $total_rooms ?></h3>
                    <p>Total Rooms</p>
                </div>
                <div class="stat-icon icon-rooms"><i class="fas fa-door-closed"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $conflicts ?></h3>
                    <p>Active Conflicts</p>
                </div>
                <div class="stat-icon icon-conflicts"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $total_users ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-icon icon-users"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Recent Booking Requests</h2>
                <span style="color:#94a3b8;">Last updated: <?= date('M j, Y g:i A') ?></span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Requested By</th>
                        <th>Room</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_bookings && $recent_bookings->num_rows > 0):
                        while ($row = $recent_bookings->fetch_assoc()):
                            $status_class = 'status-' . $row['status'];
                            $badge_class = 'badge-' . $row['user_type'];
                            $date = date('M j', strtotime($row['start_time']));
                            $time = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
                            $bk_id = str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                    ?>
                            <tr>
                                <td><strong>BK<?= $bk_id ?></strong><br><small><?= htmlspecialchars($row['reason']) ?></small>
                                    <?php if (!empty($row['urgent_needs'])): ?><br><small style="color:#f59e0b;">📌 <?= htmlspecialchars($row['urgent_needs']) ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($row['user_name']) ?></div>
                                    <small style="color:#94a3b8;"><?= htmlspecialchars($row['dept']) ?></small><br>
                                    <span class="badge <?= $badge_class ?>"><?= ucfirst($row['user_type']) ?></span>
                                </td>
                                <td><strong>Room <?= $row['room_num'] ?></strong></td>
                                <td><?= $date ?><br><small style="color:#94a3b8;"><?= $time ?></small></td>
                                <td><span class="status <?= $status_class ?>"><?= ucfirst($row['status']) ?></span></td>
                                <td>
                                    <a href="admin_view_booking.php?id=<?= $row['id'] ?>" class="btn btn-view"><i class="fas fa-eye"></i> View</a>
                                    <?php if (in_array($row['status'], ['pending', 'approved'])): ?>
                                        <button type="button" class="btn btn-danger btn-cancel" onclick="openCancelModal(<?= $row['id'] ?>)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:3rem;color:#94a3b8;">No recent bookings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="quick-actions">
                <a href="admin_manage_bookings.php" class="btn btn-primary btn-large"><i class="fas fa-calendar-check"></i> Review All Bookings</a>
                <button type="button" class="btn btn-success btn-large" onclick="document.getElementById('addRoomModal').style.display='flex'"><i class="fas fa-plus"></i> Add New Room</button>
                <a href="admin_conflicts_manage.php" class="btn btn-warning btn-large"><i class="fas fa-exclamation-triangle"></i> Resolve Conflicts</a>
            </div>
        </div>

        <!-- ================= NEW SECTION: REPORTED ROOM PROBLEMS ================= -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Reported Room Issues</h2>
                <span style="color:#94a3b8;">Feedback from users</span>
            </div>

            <?php if ($problems_result && $problems_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Reported Issue</th>
                            <th>By</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($prob = $problems_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>Room <?= htmlspecialchars($prob['room_num']) ?></strong></td>
                                <td><?= nl2br(htmlspecialchars($prob['problems'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($prob['reported_by']) ?>
                                    <small style="color:#94a3b8;">(<?= $prob['user_type'] ?>)</small>
                                </td>
                                <td><?= date('M d, Y h:i A', strtotime($prob['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="review_id" value="<?= $prob['review_id'] ?>">
                                        <button type="submit" name="resolve_problem" class="btn btn-success">
                                            <i class="fas fa-check"></i> Mark Resolved
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align:center;padding:3rem;color:#94a3b8;">
                    <i class="fas fa-check-circle" style="font-size:3rem;color:#10b981;margin-bottom:1rem;"></i><br>
                    No reported issues at the moment.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal" id="cancelBookingModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeCancelModal()">×</button>
            <h2 style="color:#e74c3c;margin-bottom:1.5rem;"><i class="fas fa-times-circle"></i> Cancel Booking</h2>
            <form method="POST">
                <input type="hidden" name="booking_id" id="cancel_booking_id">
                <div class="form-group">
                    <label class="form-label">Reason for cancellation <span>*</span></label>
                    <textarea name="cancel_reason" class="form-control" rows="4" placeholder="Please provide a reason..." required></textarea>
                </div>
                <div style="text-align:right;margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn" style="background:#334155;color:#cbd5e1;" onclick="closeCancelModal()">Cancel</button>
                    <button type="submit" name="cancel_booking" class="btn btn-danger"><i class="fas fa-times"></i> Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="addRoomModal">
        <div class="modal-content">
            <button class="close-modal" onclick="this.closest('.modal').style.display='none'">×</button>
            <div class="modal-header">
                <i class="fas fa-door-closed"></i>
                <h2>Add New Classroom</h2>
            </div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Room Number <span>*</span></label>
                        <input type="number" name="room_num" class="form-control" placeholder="e.g. 101" required min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Floor Number <span>*</span></label>
                        <input type="number" name="floor_num" class="form-control" placeholder="e.g. 3" required min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room Type <span>*</span></label>
                        <select name="type_name" class="form-control" required>
                            <option value="">Select type</option>
                            <?php
                            if ($room_types) {
                                $room_types->data_seek(0);
                                while ($type = $room_types->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($type['type_name']) ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                            <?php endwhile;
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Seating Capacity <span>*</span></label>
                        <input type="number" name="capacity" class="form-control" placeholder="e.g. 60" required min="10">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Available Facilities</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="projector" id="proj"><label for="proj">Projector</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="ac" id="ac" checked><label for="ac">Air Conditioning</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="speaker" id="spk"><label for="spk">Speakers / Audio</label>
                        </div>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Notes or Known Issues</label>
                    <textarea name="any_prob" class="form-control" rows="3" placeholder="Optional: e.g. Projector needs repair..."></textarea>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn" style="background:#334155;color:#cbd5e1;" onclick="this.closest('.modal').style.display='none'">Cancel</button>
                    <button type="submit" name="add_room" class="btn btn-success btn-large"><i class="fas fa-plus-circle"></i> Add Room</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.toggle('visible');
            sidebar.classList.toggle('hidden');
        });

        function openCancelModal(bookingId) {
            document.getElementById('cancel_booking_id').value = bookingId;
            document.getElementById('cancelBookingModal').style.display = 'flex';
        }

        function closeCancelModal() {
            document.getElementById('cancelBookingModal').style.display = 'none';
            document.querySelector('#cancelBookingModal textarea').value = '';
        }

        window.onclick = function(event) {
            const cancelModal = document.getElementById('cancelBookingModal');
            const addModal = document.getElementById('addRoomModal');
            if (event.target === cancelModal) closeCancelModal();
            if (event.target === addModal) addModal.style.display = 'none';
        }

        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.8s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 800);
            });
        }, 5000);
    </script>
</body>
</html>