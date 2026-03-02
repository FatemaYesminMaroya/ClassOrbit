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
$user_initial = strtoupper(substr($user_name, 0, 2));

/* ================= DB CONNECTION ================= */
$conn = new mysqli("localhost", "root", "", "classorbit");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ================= PROCESS FORMS ================= */
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ===== Add New Room ===== */
    if (isset($_POST['add_room'])) {
        $room_num  = (int) $_POST['room_num'];
        $floor_num = (int) $_POST['floor_num'];
        $capacity  = (int) $_POST['capacity'];
        $type_name = $conn->real_escape_string($_POST['type_name']);
        $wing      = $conn->real_escape_string($_POST['wing'] ?? '');
        $any_prob  = $conn->real_escape_string($_POST['any_prob'] ?? '');
        
        $projector = isset($_POST['projector']) ? 'Yes' : 'No';
        $ac        = isset($_POST['ac']) ? 'Yes' : 'No';
        $speaker   = isset($_POST['speaker']) ? 'Yes' : 'No';
        $whiteboard = isset($_POST['whiteboard']) ? 'Yes' : 'No';
        $computer  = isset($_POST['computer']) ? 'Yes' : 'No';
        
        // Combine facilities for display
        $facilities = [];
        if ($projector === 'Yes') $facilities[] = 'Projector';
        if ($ac === 'Yes') $facilities[] = 'Air Conditioning';
        if ($speaker === 'Yes') $facilities[] = 'Speaker';
        if ($whiteboard === 'Yes') $facilities[] = 'Whiteboard';
        if ($computer === 'Yes') $facilities[] = 'Computer Lab';
        
        $check = $conn->prepare("SELECT id FROM class_room WHERE room_num = ?");
        $check->bind_param("i", $room_num);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Room number already exists!</div>";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO class_room 
                (room_num, floor_num, capacity, type_name, projector, AC, speaker, whiteboard, computer_lab, any_prob)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiisssssss",
                $room_num,
                $floor_num,
                $capacity,
                $type_name,
                $projector,
                $ac,
                $speaker,
                $whiteboard,
                $computer,
                $any_prob
            );
            
            if ($stmt->execute()) {
                $room_id = $stmt->insert_id;
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> New room added successfully! (Room $room_num)</div>";
            } else {
                $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error adding room: " . $conn->error . "</div>";
            }
            $stmt->close();
        }
        $check->close();
    }
    
    /* ===== Update Room ===== */
    elseif (isset($_POST['update_room'])) {
        $room_id   = (int) $_POST['room_id'];
        $room_num  = (int) $_POST['room_num'];
        $floor_num = (int) $_POST['floor_num'];
        $capacity  = (int) $_POST['capacity'];
        $type_name = $conn->real_escape_string($_POST['type_name']);
        $any_prob  = $conn->real_escape_string($_POST['any_prob'] ?? '');
        
        $projector = isset($_POST['projector']) ? 'Yes' : 'No';
        $ac        = isset($_POST['ac']) ? 'Yes' : 'No';
        $speaker   = isset($_POST['speaker']) ? 'Yes' : 'No';
        $whiteboard = isset($_POST['whiteboard']) ? 'Yes' : 'No';
        $computer  = isset($_POST['computer']) ? 'Yes' : 'No';
        
        // Check if room number already exists for another room
        $check = $conn->prepare("SELECT id FROM class_room WHERE room_num = ? AND id != ?");
        $check->bind_param("ii", $room_num, $room_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Room number already exists for another room!</div>";
        } else {
            $stmt = $conn->prepare("
                UPDATE class_room SET 
                room_num = ?, floor_num = ?, capacity = ?, type_name = ?, 
                projector = ?, AC = ?, speaker = ?, whiteboard = ?, computer_lab = ?, any_prob = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "iiisssssssi",
                $room_num,
                $floor_num,
                $capacity,
                $type_name,
                $projector,
                $ac,
                $speaker,
                $whiteboard,
                $computer,
                $any_prob,
                $room_id
            );
            
            if ($stmt->execute()) {
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Room updated successfully! (Room $room_num)</div>";
            } else {
                $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error updating room: " . $conn->error . "</div>";
            }
            $stmt->close();
        }
        $check->close();
    }
    
    /* ===== Delete Room ===== */
    elseif (isset($_POST['delete_room'])) {
        $room_id = (int) $_POST['room_id'];
        
        // Check if room has any bookings
        $check = $conn->prepare("
            SELECT COUNT(*) as booking_count 
            FROM room_booking rb 
            JOIN checking c ON rb.checking_id = c.id 
            WHERE c.class_room_id = ? AND rb.status IN ('pending', 'approved')
        ");
        $check->bind_param("i", $room_id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        
        if ($result['booking_count'] > 0) {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Cannot delete room with active or pending bookings!</div>";
        } else {
            // Delete related checking slots first
            $delete_checking = $conn->prepare("DELETE FROM checking WHERE class_room_id = ?");
            $delete_checking->bind_param("i", $room_id);
            $delete_checking->execute();
            $delete_checking->close();
            
            // Now delete the room
            $stmt = $conn->prepare("DELETE FROM class_room WHERE id = ?");
            $stmt->bind_param("i", $room_id);
            
            if ($stmt->execute()) {
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Room deleted successfully!</div>";
            } else {
                $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error deleting room: " . $conn->error . "</div>";
            }
            $stmt->close();
        }
        $check->close();
    }
}

/* ================= FETCH ROOM DATA ================= */

// Get all rooms with their current status
$rooms_query = "
    SELECT 
        cr.*,
        (SELECT COUNT(*) FROM checking c 
         WHERE c.class_room_id = cr.id 
         AND c.is_available = 0 
         AND c.start_time <= NOW() 
         AND c.end_time >= NOW()) as current_bookings,
        (CASE 
            WHEN cr.any_prob LIKE '%maintenance%' OR cr.any_prob LIKE '%repair%' THEN 'maintenance'
            WHEN (SELECT COUNT(*) FROM checking c 
                  WHERE c.class_room_id = cr.id 
                  AND c.is_available = 0 
                  AND c.start_time <= NOW() 
                  AND c.end_time >= NOW()) > 0 THEN 'booked'
            ELSE 'available'
        END) as current_status
    FROM class_room cr
    ORDER BY cr.floor_num, cr.room_num
";

$rooms_result = $conn->query($rooms_query);
$all_rooms = [];
$total_rooms = 0;
$available_rooms = 0;
$booked_rooms = 0;
$maintenance_rooms = 0;

if ($rooms_result && $rooms_result->num_rows > 0) {
    while ($room = $rooms_result->fetch_assoc()) {
        // Get facilities
        $facilities = [];
        if ($room['projector'] === 'Yes') $facilities[] = 'Projector';
        if ($room['AC'] === 'Yes') $facilities[] = 'Air Conditioning';
        if ($room['speaker'] === 'Yes') $facilities[] = 'Speaker';
        if ($room['whiteboard'] === 'Yes') $facilities[] = 'Whiteboard';
        if ($room['computer_lab'] === 'Yes') $facilities[] = 'Computer Lab';
        
        $room['facilities'] = $facilities;
        $room['status'] = $room['current_status'];
        $all_rooms[] = $room;
        
        // Update stats
        $total_rooms++;
        switch ($room['current_status']) {
            case 'available': $available_rooms++; break;
            case 'booked': $booked_rooms++; break;
            case 'maintenance': $maintenance_rooms++; break;
        }
    }
}

// Get all room types for dropdown
$room_types_result = $conn->query("SELECT DISTINCT type_name FROM type ORDER BY type_name");
$room_types = [];
if ($room_types_result) {
    while ($type = $room_types_result->fetch_assoc()) {
        $room_types[] = $type['type_name'];
    }
}

// Get all floors for filter
$floors_result = $conn->query("SELECT DISTINCT floor_num FROM class_room ORDER BY floor_num");
$floors = [];
if ($floors_result) {
    while ($floor = $floors_result->fetch_assoc()) {
        $floors[] = $floor['floor_num'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | ClassOrbit</title>
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
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }

        nav {
            background: rgba(15, 23, 42, 0.95);
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
            color: #fff;
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
            display: flex;
            align-items: center;
            gap: 1rem;
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

        .alert.warning {
            background: rgba(243, 156, 18, 0.2);
            border: 1px solid #f39c12;
            color: #fef9e7;
        }

        .alert.info {
            background: rgba(52, 152, 219, 0.2);
            border: 1px solid #3498db;
            color: #d1ecf1;
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

        .filters {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 180px;
        }

        .filter-label {
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .filter-select,
        .filter-input {
            padding: 0.8rem 1rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #f1f5f9;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .filter-select:focus,
        .filter-input:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
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
            display: inline-block;
        }

        .status-available {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        .status-booked {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .status-maintenance {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }

        .status-unavailable {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .facility-tag {
            display: inline-block;
            background: rgba(30, 41, 59, 0.5);
            padding: 0.3rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            margin: 0.2rem;
            color: #cbd5e1;
            border: 1px solid #334155;
        }

        .facilities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
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

        .btn-danger {
            background: linear-gradient(145deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(145deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #334155;
            color: #cbd5e1;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

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
            max-width: 700px;
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

        .quick-actions {
            display: flex;
            gap: 1.5rem;
            margin-top: 2.5rem;
            flex-wrap: wrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
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

        .icon-total {
            background: rgba(142, 68, 173, 0.2);
            color: #8e44ad;
        }

        .icon-available {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        .icon-booked {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .icon-maintenance {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
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
                padding: 140px 1.5rem 3rem;
            }

            .nav-container {
                padding: 0 1.5rem;
            }

            .quick-actions,
            .filters {
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .filter-group {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-container">
            <div class="logo-section">
                <i class="fas fa-graduation-cap" style="font-size:1.75rem;"></i>
                <span class="logoName">Class<span class="orbitclass">Orbit</span></span>
            </div>
            <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
        </div>
    </nav>
    
    <aside class="sidebar" id="sidebar">
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_manage_bookings.php"><i class="fas fa-calendar-check"></i> Manage Bookings</a></li>
            <li><a href="admin_manage_rooms.php" class="active"><i class="fas fa-door-closed"></i> Manage Rooms</a></li>
            <li><a href="admin_conflicts_manage.php"><i class="fas fa-exclamation-triangle"></i> Conflicts</a></li>
            <li><a href="admin_system_settings.php"><i class="fas fa-cogs"></i> System Settings</a></li>
            <li class="logout"><a href="?logout=1"><i class="fas fa-power-off"></i> Logout</a></li>
        </ul>
    </aside>
    
    <main>
        <div class="header">
            <h1>Manage Rooms</h1>
            <div class="user-info">
                <div class="user-avatar"><?= htmlspecialchars($user_initial) ?></div>
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
                    <h3 id="totalRooms"><?= $total_rooms ?></h3>
                    <p>Total Rooms</p>
                </div>
                <div class="stat-icon icon-total"><i class="fas fa-door-closed"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3 id="availableRooms"><?= $available_rooms ?></h3>
                    <p>Available Rooms</p>
                </div>
                <div class="stat-icon icon-available"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3 id="bookedRooms"><?= $booked_rooms ?></h3>
                    <p>Currently Booked</p>
                </div>
                <div class="stat-icon icon-booked"><i class="fas fa-calendar-alt"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3 id="maintenanceRooms"><?= $maintenance_rooms ?></h3>
                    <p>Under Maintenance</p>
                </div>
                <div class="stat-icon icon-maintenance"><i class="fas fa-tools"></i></div>
            </div>
        </div>
        
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Classroom Management</h2>
                <button class="btn btn-primary" onclick="openAddRoomModal()"><i class="fas fa-plus"></i> Add New Room</button>
            </div>
            
            <div class="filters">
                <div class="filter-group">
                    <label class="filter-label">Room Status</label>
                    <select class="filter-select" id="filterStatus" onchange="filterRooms()">
                        <option value="all">All Rooms</option>
                        <option value="available">Available</option>
                        <option value="booked">Booked</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Floor</label>
                    <select class="filter-select" id="filterFloor" onchange="filterRooms()">
                        <option value="all">All Floors</option>
                        <?php foreach ($floors as $floor): ?>
                            <option value="<?= $floor ?>">Floor <?= $floor ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Room Type</label>
                    <select class="filter-select" id="filterType" onchange="filterRooms()">
                        <option value="all">All Types</option>
                        <?php foreach ($room_types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Capacity</label>
                    <select class="filter-select" id="filterCapacity" onchange="filterRooms()">
                        <option value="all">Any Capacity</option>
                        <option value="small">Small (&lt; 30)</option>
                        <option value="medium">Medium (30-60)</option>
                        <option value="large">Large (&gt; 60)</option>
                    </select>
                </div>
                <button class="btn btn-outline" style="align-self:flex-end;margin-bottom:0.5rem;" onclick="resetFilters()">Reset Filters</button>
            </div>
            
            <table id="roomsTable">
                <thead>
                    <tr>
                        <th>Room No</th>
                        <th>Type</th>
                        <th>Floor</th>
                        <th>Capacity</th>
                        <th>Facilities</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="roomsTableBody">
                    <?php if (count($all_rooms) > 0): ?>
                        <?php foreach ($all_rooms as $room): ?>
                            <?php 
                            $status_text = ucfirst($room['status']);
                            $status_class = 'status-' . $room['status'];
                            ?>
                            <tr data-id="<?= $room['id'] ?>" data-floor="<?= $room['floor_num'] ?>" data-type="<?= htmlspecialchars($room['type_name']) ?>" data-status="<?= $room['status'] ?>" data-capacity="<?= $room['capacity'] ?>">
                                <td>
                                    <strong>Room <?= htmlspecialchars($room['room_num']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($room['type_name']) ?></td>
                                <td>Floor <?= htmlspecialchars($room['floor_num']) ?></td>
                                <td><?= htmlspecialchars($room['capacity']) ?> seats</td>
                                <td>
                                    <div class="facilities-list">
                                        <?php foreach ($room['facilities'] as $facility): ?>
                                            <span class="facility-tag"><?= htmlspecialchars($facility) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td><span class="status <?= $status_class ?>"><?= $status_text ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" onclick="editRoom(<?= $room['id'] ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-outline btn-sm" onclick="viewRoomDetails(<?= $room['id'] ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteRoom(<?= $room['id'] ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:3rem;color:#94a3b8;">
                                No rooms found in the system. Add your first room using the "Add New Room" button.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="quick-actions">
                <a href="admin_dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </main>
    
    <!-- Add Room Modal -->
    <div class="modal" id="addRoomModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('addRoomModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-door-closed"></i>
                <h2>Add New Classroom</h2>
            </div>
            <form method="POST" id="addRoomForm">
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
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Seating Capacity <span>*</span></label>
                        <input type="number" name="capacity" class="form-control" placeholder="e.g. 40" required min="10">
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Available Facilities</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="projector" id="proj" value="1">
                            <label for="proj">Projector</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="ac" id="ac" value="1" checked>
                            <label for="ac">Air Conditioning</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="speaker" id="speaker" value="1">
                            <label for="speaker">Speakers / Audio</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="whiteboard" id="whiteboard" value="1" checked>
                            <label for="whiteboard">Whiteboard</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="computer" id="computer" value="1">
                            <label for="computer">Computer Lab</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Building Wing</label>
                        <input type="text" name="wing" class="form-control" placeholder="e.g. North, Science">
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Notes or Known Issues</label>
                    <textarea name="any_prob" class="form-control" rows="3" placeholder="Optional: e.g. Projector needs repair, Under maintenance until..."></textarea>
                </div>
                
                <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addRoomModal')">Cancel</button>
                    <button type="submit" name="add_room" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Add Room
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Room Modal -->
    <div class="modal" id="editRoomModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('editRoomModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-edit"></i>
                <h2>Edit Room Details</h2>
            </div>
            <form method="POST" id="editRoomForm">
                <input type="hidden" id="editRoomId" name="room_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Room Number <span>*</span></label>
                        <input type="number" id="editRoomNum" name="room_num" class="form-control" required min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Floor Number <span>*</span></label>
                        <input type="number" id="editFloorNum" name="floor_num" class="form-control" required min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room Type <span>*</span></label>
                        <select id="editTypeName" name="type_name" class="form-control" required>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Seating Capacity <span>*</span></label>
                        <input type="number" id="editCapacity" name="capacity" class="form-control" required min="10">
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Available Facilities</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="projector" id="editProj" value="1">
                            <label for="editProj">Projector</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="ac" id="editAc" value="1">
                            <label for="editAc">Air Conditioning</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="speaker" id="editSpeaker" value="1">
                            <label for="editSpeaker">Speakers / Audio</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="whiteboard" id="editWhiteboard" value="1">
                            <label for="editWhiteboard">Whiteboard</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="computer" id="editComputer" value="1">
                            <label for="editComputer">Computer Lab</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Notes or Known Issues</label>
                    <textarea id="editAnyProb" name="any_prob" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editRoomModal')">Cancel</button>
                    <button type="submit" name="update_room" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Room Modal -->
    <div class="modal" id="deleteRoomModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('deleteRoomModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-trash" style="color:#e74c3c;"></i>
                <h2 style="color:#e74c3c;">Delete Room</h2>
            </div>
            <form method="POST" id="deleteRoomForm">
                <input type="hidden" id="deleteRoomId" name="room_id">
                <p style="margin-bottom:1.5rem;color:#cbd5e1;">
                    Are you sure you want to delete this room? This action cannot be undone. 
                    All associated booking slots will also be deleted.
                </p>
                <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('deleteRoomModal')">Cancel</button>
                    <button type="submit" name="delete_room" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Room
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Room Details Modal -->
    <div class="modal" id="viewRoomModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('viewRoomModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-door-closed"></i>
                <h2 id="viewRoomTitle">Room Details</h2>
            </div>
            <div id="viewRoomContent" style="padding:1rem 0;color:#cbd5e1;"></div>
        </div>
    </div>
    
    <script>
        // Toggle sidebar
        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('visible');
            sidebar.classList.toggle('hidden');
        });
        
        // Filter rooms function
        function filterRooms() {
            const statusFilter = document.getElementById('filterStatus').value;
            const floorFilter = document.getElementById('filterFloor').value;
            const typeFilter = document.getElementById('filterType').value;
            const capacityFilter = document.getElementById('filterCapacity').value;
            
            const rows = document.querySelectorAll('#roomsTableBody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                if (row.cells.length < 7) return; // Skip empty row
                
                const rowStatus = row.dataset.status;
                const rowFloor = row.dataset.floor;
                const rowType = row.dataset.type;
                const rowCapacity = parseInt(row.dataset.capacity);
                
                let statusMatch = (statusFilter === 'all' || rowStatus === statusFilter);
                let floorMatch = (floorFilter === 'all' || rowFloor === floorFilter);
                let typeMatch = (typeFilter === 'all' || rowType === typeFilter);
                let capacityMatch = true;
                
                if (capacityFilter !== 'all') {
                    if (capacityFilter === 'small') capacityMatch = (rowCapacity < 30);
                    else if (capacityFilter === 'medium') capacityMatch = (rowCapacity >= 30 && rowCapacity <= 60);
                    else if (capacityFilter === 'large') capacityMatch = (rowCapacity > 60);
                }
                
                if (statusMatch && floorMatch && typeMatch && capacityMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show message if no rooms match
            const noRoomsRow = document.querySelector('#roomsTableBody tr[style*="display: none"]');
            if (visibleCount === 0 && rows.length > 0) {
                document.getElementById('roomsTableBody').innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align:center;padding:3rem;color:#94a3b8;">
                            No rooms found matching the selected filters.
                        </td>
                    </tr>
                `;
            }
        }
        
        function resetFilters() {
            document.getElementById('filterStatus').value = 'all';
            document.getElementById('filterFloor').value = 'all';
            document.getElementById('filterType').value = 'all';
            document.getElementById('filterCapacity').value = 'all';
            
            const rows = document.querySelectorAll('#roomsTableBody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
            
            // Reload the page to show all rooms again
            window.location.reload();
        }
        
        function openAddRoomModal() {
            document.getElementById('addRoomModal').style.display = 'flex';
            document.getElementById('addRoomForm').reset();
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Get room data from PHP array for editing
        const roomData = <?= json_encode($all_rooms) ?>;
        
        function editRoom(roomId) {
            const room = roomData.find(r => r.id == roomId);
            if (!room) return;
            
            document.getElementById('editRoomId').value = room.id;
            document.getElementById('editRoomNum').value = room.room_num;
            document.getElementById('editFloorNum').value = room.floor_num;
            document.getElementById('editCapacity').value = room.capacity;
            document.getElementById('editTypeName').value = room.type_name;
            document.getElementById('editAnyProb').value = room.any_prob || '';
            
            // Set checkboxes
            document.getElementById('editProj').checked = (room.projector === 'Yes');
            document.getElementById('editAc').checked = (room.AC === 'Yes');
            document.getElementById('editSpeaker').checked = (room.speaker === 'Yes');
            document.getElementById('editWhiteboard').checked = (room.whiteboard === 'Yes');
            document.getElementById('editComputer').checked = (room.computer_lab === 'Yes');
            
            document.getElementById('editRoomModal').style.display = 'flex';
        }
        
        function viewRoomDetails(roomId) {
            const room = roomData.find(r => r.id == roomId);
            if (!room) return;
            
            // Build facilities list
            let facilitiesHtml = '';
            const facilities = [];
            if (room.projector === 'Yes') facilities.push('Projector');
            if (room.AC === 'Yes') facilities.push('Air Conditioning');
            if (room.speaker === 'Yes') facilities.push('Speaker');
            if (room.whiteboard === 'Yes') facilities.push('Whiteboard');
            if (room.computer_lab === 'Yes') facilities.push('Computer Lab');
            
            facilities.forEach(facility => {
                facilitiesHtml += `<span class="facility-tag">${facility}</span>`;
            });
            
            // Determine status with class
            let statusClass = 'status-available';
            let statusText = 'Available';
            if (room.status === 'booked') {
                statusClass = 'status-booked';
                statusText = 'Booked';
            } else if (room.status === 'maintenance') {
                statusClass = 'status-maintenance';
                statusText = 'Under Maintenance';
            } else if (room.status === 'unavailable') {
                statusClass = 'status-unavailable';
                statusText = 'Unavailable';
            }
            
            // Build content
            const content = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div>
                        <p><strong>Room Number:</strong> ${room.room_num}</p>
                        <p><strong>Floor:</strong> ${room.floor_num}</p>
                        <p><strong>Type:</strong> ${room.type_name}</p>
                    </div>
                    <div>
                        <p><strong>Capacity:</strong> ${room.capacity} seats</p>
                        <p><strong>Status:</strong> <span class="status ${statusClass}">${statusText}</span></p>
                        <p><strong>Current Bookings:</strong> ${room.current_bookings || 0}</p>
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <p><strong>Facilities:</strong></p>
                    <div class="facilities-list" style="margin-top: 0.5rem;">
                        ${facilitiesHtml || '<span style="color:#94a3b8;">No facilities listed</span>'}
                    </div>
                </div>
                ${room.any_prob ? `
                <div style="margin-bottom: 1.5rem;">
                    <p><strong>Notes/Issues:</strong></p>
                    <p style="background: rgba(243,156,18,0.1); padding: 1rem; border-radius: 8px; border-left: 4px solid #f39c12;">
                        ${room.any_prob}
                    </p>
                </div>` : ''}
            `;
            
            document.getElementById('viewRoomTitle').textContent = `Room ${room.room_num} Details`;
            document.getElementById('viewRoomContent').innerHTML = content;
            document.getElementById('viewRoomModal').style.display = 'flex';
        }
        
        function deleteRoom(roomId) {
            document.getElementById('deleteRoomId').value = roomId;
            document.getElementById('deleteRoomModal').style.display = 'flex';
        }
        
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };
        
        // Auto-hide alerts after 5 seconds
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