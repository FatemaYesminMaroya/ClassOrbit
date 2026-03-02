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
    /* ===== Add New User ===== */
    if (isset($_POST['add_user'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_type = $_POST['user_type'];
        $dept = $conn->real_escape_string($_POST['dept'] ?? '');
        
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM admin WHERE email = ? 
                                 UNION SELECT id FROM faculty WHERE email = ? 
                                 UNION SELECT id FROM student WHERE email = ? 
                                 UNION SELECT id FROM club WHERE email = ?");
        $check->bind_param("ssss", $email, $email, $email, $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Email already exists in the system!</div>";
        } else {
            switch ($user_type) {
                case 'admin':
                    $stmt = $conn->prepare("INSERT INTO admin (name, email, password) VALUES (?, ?, ?)");
                    break;
                case 'faculty':
                    $stmt = $conn->prepare("INSERT INTO faculty (name, email, password, dept, priority_id) VALUES (?, ?, ?, ?, 1)");
                    $stmt->bind_param("ssss", $name, $email, $password, $dept);
                    break;
                case 'student':
                    $stmt = $conn->prepare("INSERT INTO student (name, email, password, dept, priority_id) VALUES (?, ?, ?, ?, 3)");
                    $stmt->bind_param("ssss", $name, $email, $password, $dept);
                    break;
                case 'club':
                    $stmt = $conn->prepare("INSERT INTO club (name, email, password, clubname, priority_id) VALUES (?, ?, ?, ?, 2)");
                    $stmt->bind_param("ssss", $name, $email, $password, $dept);
                    break;
                default:
                    $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Invalid user type!</div>";
                    break;
            }
            
            if (isset($stmt) && $user_type !== 'faculty' && $user_type !== 'student' && $user_type !== 'club') {
                $stmt->bind_param("sss", $name, $email, $password);
            }
            
            if (isset($stmt) && $stmt->execute()) {
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> New $user_type added successfully!</div>";
            } elseif (!isset($stmt)) {
                $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Failed to add user.</div>";
            }
            
            if (isset($stmt)) $stmt->close();
        }
        $check->close();
    }
    
    /* ===== Delete User ===== */
    elseif (isset($_POST['delete_user'])) {
        $user_id = (int) $_POST['user_id'];
        $user_type = $_POST['user_type'];
        
        // Check if user has any bookings
        $check_bookings = $conn->prepare("
            SELECT COUNT(*) as booking_count 
            FROM room_booking 
            WHERE user_id = ? AND priority_id = ?
        ");
        
        $priority_id = 0;
        switch ($user_type) {
            case 'admin': $priority_id = 0; break;
            case 'faculty': $priority_id = 1; break;
            case 'student': $priority_id = 3; break;
            case 'club': $priority_id = 2; break;
        }
        
        $check_bookings->bind_param("ii", $user_id, $priority_id);
        $check_bookings->execute();
        $result = $check_bookings->get_result()->fetch_assoc();
        
        if ($result['booking_count'] > 0) {
            $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Cannot delete user with active bookings!</div>";
        } else {
            switch ($user_type) {
                case 'admin':
                    $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
                    break;
                case 'faculty':
                    $stmt = $conn->prepare("DELETE FROM faculty WHERE id = ?");
                    break;
                case 'student':
                    $stmt = $conn->prepare("DELETE FROM student WHERE id = ?");
                    break;
                case 'club':
                    $stmt = $conn->prepare("DELETE FROM club WHERE id = ?");
                    break;
            }
            
            if (isset($stmt)) {
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> User deleted successfully!</div>";
                } else {
                    $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Failed to delete user.</div>";
                }
                $stmt->close();
            }
        }
        $check_bookings->close();
    }
    
    /* ===== Update System Settings ===== */
    elseif (isset($_POST['save_settings'])) {
        $max_hours = (int) $_POST['max_hours'];
        $advance_days = (int) $_POST['advance_days'];
        $auto_cancel = (int) $_POST['auto_cancel'];
        $auto_approve_faculty = isset($_POST['auto_approve_faculty']) ? 1 : 0;
        $send_conflict_alerts = isset($_POST['send_conflict_alerts']) ? 1 : 0;
        $allow_overtime = isset($_POST['allow_overtime']) ? 1 : 0;
        
        // Create or update settings in a settings table
        $check_settings = $conn->query("SELECT 1 FROM system_settings LIMIT 1");
        
        if ($check_settings->num_rows > 0) {
            // Update existing settings
            $stmt = $conn->prepare("
                UPDATE system_settings SET 
                max_booking_hours = ?,
                advance_booking_days = ?,
                auto_cancel_hours = ?,
                auto_approve_faculty = ?,
                send_conflict_alerts = ?,
                allow_overtime = ?,
                updated_at = NOW()
            ");
        } else {
            // Create settings table if it doesn't exist
            $conn->query("
                CREATE TABLE IF NOT EXISTS system_settings (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    max_booking_hours INT DEFAULT 4,
                    advance_booking_days INT DEFAULT 30,
                    auto_cancel_hours INT DEFAULT 48,
                    auto_approve_faculty BOOLEAN DEFAULT TRUE,
                    send_conflict_alerts BOOLEAN DEFAULT TRUE,
                    allow_overtime BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            $stmt = $conn->prepare("
                INSERT INTO system_settings 
                (max_booking_hours, advance_booking_days, auto_cancel_hours, 
                 auto_approve_faculty, send_conflict_alerts, allow_overtime)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
        }
        
        $stmt->bind_param("iiiiii", $max_hours, $advance_days, $auto_cancel, 
                         $auto_approve_faculty, $send_conflict_alerts, $allow_overtime);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> System settings saved successfully!</div>";
        } else {
            $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Failed to save settings.</div>";
        }
        $stmt->close();
    }
}

/* ================= FETCH SYSTEM DATA ================= */

// Get all users from database
$all_users = [];

// Get admins
$admins_result = $conn->query("SELECT id, name, email, 'admin' as type, 'Administration' as dept FROM admin");
while ($admin = $admins_result->fetch_assoc()) {
    $all_users[] = $admin;
}

// Get faculty
$faculty_result = $conn->query("SELECT id, name, email, 'faculty' as type, dept FROM faculty");
while ($faculty = $faculty_result->fetch_assoc()) {
    $all_users[] = $faculty;
}

// Get students
$students_result = $conn->query("SELECT id, name, email, 'student' as type, dept FROM student");
while ($student = $students_result->fetch_assoc()) {
    $all_users[] = $student;
}

// Get clubs
$clubs_result = $conn->query("SELECT id, name, email, 'club' as type, clubname as dept FROM club");
while ($club = $clubs_result->fetch_assoc()) {
    $all_users[] = $club;
}

// Get system settings
$settings = [
    'max_booking_hours' => 4,
    'advance_booking_days' => 30,
    'auto_cancel_hours' => 48,
    'auto_approve_faculty' => true,
    'send_conflict_alerts' => true,
    'allow_overtime' => true
];

// Check if settings table exists
$settings_result = $conn->query("SELECT * FROM system_settings LIMIT 1");
if ($settings_result && $settings_result->num_rows > 0) {
    $settings = $settings_result->fetch_assoc();
    // Convert boolean values
    $settings['auto_approve_faculty'] = (bool)$settings['auto_approve_faculty'];
    $settings['send_conflict_alerts'] = (bool)$settings['send_conflict_alerts'];
    $settings['allow_overtime'] = (bool)$settings['allow_overtime'];
}

// Get priority system info
$priority_result = $conn->query("SELECT * FROM priority ORDER BY priority_id");
$priorities = [];
while ($priority = $priority_result->fetch_assoc()) {
    $priorities[] = $priority;
}

// Get departments for dropdown
$depts_result = $conn->query("SELECT DISTINCT dept FROM faculty UNION SELECT DISTINCT dept FROM student ORDER BY dept");
$departments = [];
while ($dept = $depts_result->fetch_assoc()) {
    $departments[] = $dept['dept'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | ClassOrbit</title>
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

        .alert.error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #f8d7da;
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

        .btn-outline {
            background: transparent;
            border: 1px solid #334155;
            color: #cbd5e1;
        }

        .btn-danger {
            background: linear-gradient(145deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .setting-card {
            background: rgba(30, 41, 59, 0.7);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid #334155;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #cbd5e1;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #f1f5f9;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
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

        .user-list-item {
            padding: 1rem;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }

        .user-list-item:hover {
            background: rgba(245, 158, 11, 0.05);
        }

        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f59e0b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-admin {
            background: rgba(142, 68, 173, 0.2);
            color: #8e44ad;
        }

        .badge-faculty {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .badge-student {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        .badge-club {
            background: rgba(230, 126, 34, 0.2);
            color: #e67e22;
        }

        .badge-active {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        .badge-inactive {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .priority-item {
            padding: 0.8rem;
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.5);
            margin-bottom: 0.8rem;
            border-left: 4px solid;
        }

        .priority-high {
            border-left-color: #e74c3c;
        }

        .priority-medium {
            border-left-color: #f39c12;
        }

        .priority-low {
            border-left-color: #27ae60;
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
            max-width: 500px;
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

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #1e293b;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .stat-info p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .icon-admin {
            background: rgba(142, 68, 173, 0.2);
            color: #8e44ad;
        }

        .icon-faculty {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .icon-student {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        .icon-club {
            background: rgba(230, 126, 34, 0.2);
            color: #e67e22;
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

            .settings-grid {
                grid-template-columns: 1fr;
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
            <li><a href="admin_manage_rooms.php"><i class="fas fa-door-closed"></i> Manage Rooms</a></li>
            <li><a href="admin_conflicts_manage.php"><i class="fas fa-exclamation-triangle"></i> Conflicts</a></li>
            <li><a href="admin_system_settings.php" class="active"><i class="fas fa-cogs"></i> System Settings</a></li>
            <li class="logout"><a href="?logout=1"><i class="fas fa-power-off"></i> Logout</a></li>
        </ul>
    </aside>
    
    <main>
        <div class="header">
            <h1>System Settings</h1>
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
            <?php
            $admin_count = 0;
            $faculty_count = 0;
            $student_count = 0;
            $club_count = 0;
            
            foreach ($all_users as $user) {
                switch ($user['type']) {
                    case 'admin': $admin_count++; break;
                    case 'faculty': $faculty_count++; break;
                    case 'student': $student_count++; break;
                    case 'club': $club_count++; break;
                }
            }
            ?>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $admin_count ?></h3>
                    <p>Administrators</p>
                </div>
                <div class="stat-icon icon-admin"><i class="fas fa-user-shield"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $faculty_count ?></h3>
                    <p>Faculty Members</p>
                </div>
                <div class="stat-icon icon-faculty"><i class="fas fa-chalkboard-teacher"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $student_count ?></h3>
                    <p>Students</p>
                </div>
                <div class="stat-icon icon-student"><i class="fas fa-user-graduate"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $club_count ?></h3>
                    <p>Clubs</p>
                </div>
                <div class="stat-icon icon-club"><i class="fas fa-users"></i></div>
            </div>
        </div>
        
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">System Configuration</h2>
                <button type="submit" form="systemSettingsForm" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
            
            <form method="POST" id="systemSettingsForm">
                <input type="hidden" name="save_settings" value="1">
                <div class="settings-grid">
                    <div class="setting-card">
                        <h3 style="margin-bottom:1.2rem;color:#f8fafc;"><i class="fas fa-users"></i> Manage Users</h3>
                        <div id="usersList" style="max-height:300px;overflow-y:auto;margin-bottom:1rem;">
                            <?php if (count($all_users) > 0): ?>
                                <?php foreach ($all_users as $user): ?>
                                    <?php 
                                    $badge_class = 'badge-' . $user['type'];
                                    $type_text = ucfirst($user['type']);
                                    ?>
                                    <div class="user-list-item" data-id="<?= $user['id'] ?>" data-type="<?= $user['type'] ?>">
                                        <div style="display:flex;align-items:center;gap:1rem;">
                                            <div class="user-avatar-small"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                                            <div>
                                                <div style="font-weight:600;"><?= htmlspecialchars($user['name']) ?></div>
                                                <div style="font-size:0.85rem;color:#94a3b8;"><?= htmlspecialchars($user['email']) ?></div>
                                                <div style="display:flex;gap:0.5rem;margin-top:0.3rem;">
                                                    <span class="badge <?= $badge_class ?>"><?= $type_text ?></span>
                                                    <?php if ($user['dept']): ?>
                                                        <span style="font-size:0.75rem;color:#94a3b8;"><?= htmlspecialchars($user['dept']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="action-buttons">
                                            <?php if ($user['type'] !== 'admin' || $user['email'] !== 'admin@classorbit.com'): ?>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser(<?= $user['id'] ?>, '<?= $user['type'] ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align:center;color:#94a3b8;padding:1rem;">No users found in the system.</p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline" style="width:100%;" onclick="showAddUserModal()">
                            <i class="fas fa-user-plus"></i> Add New User
                        </button>
                    </div>
                    
                    <div class="setting-card">
                        <h3 style="margin-bottom:1.2rem;color:#f8fafc;"><i class="fas fa-sliders-h"></i> Booking Rules</h3>
                        <div class="form-group">
                            <label class="form-label">Max Booking Duration (hours)</label>
                            <input type="number" class="form-control" name="max_hours" 
                                   value="<?= htmlspecialchars($settings['max_booking_hours']) ?>" 
                                   min="1" max="24">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Advance Booking (days)</label>
                            <input type="number" class="form-control" name="advance_days" 
                                   value="<?= htmlspecialchars($settings['advance_booking_days']) ?>" 
                                   min="1" max="365">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Auto-cancel Pending (hours)</label>
                            <input type="number" class="form-control" name="auto_cancel" 
                                   value="<?= htmlspecialchars($settings['auto_cancel_hours']) ?>" 
                                   min="1" max="168">
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="auto_approve_faculty" id="autoApproveFaculty" 
                                       <?= $settings['auto_approve_faculty'] ? 'checked' : '' ?>>
                                <label for="autoApproveFaculty">Auto-approve faculty bookings</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="send_conflict_alerts" id="sendConflictAlerts" 
                                       <?= $settings['send_conflict_alerts'] ? 'checked' : '' ?>>
                                <label for="sendConflictAlerts">Send conflict alerts</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="allow_overtime" id="allowOvertime" 
                                       <?= $settings['allow_overtime'] ? 'checked' : '' ?>>
                                <label for="allowOvertime">Allow overtime requests</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-card">
                        <h3 style="margin-bottom:1.2rem;color:#f8fafc;"><i class="fas fa-chart-line"></i> Priority System</h3>
                        <?php foreach ($priorities as $priority): ?>
                            <?php 
                            $priority_class = '';
                            $color = '#27ae60';
                            
                            if ($priority['priority_id'] == 1) {
                                $priority_class = 'priority-high';
                                $color = '#e74c3c';
                            } elseif ($priority['priority_id'] == 2) {
                                $priority_class = 'priority-medium';
                                $color = '#f39c12';
                            } else {
                                $priority_class = 'priority-low';
                                $color = '#27ae60';
                            }
                            ?>
                            <div class="priority-item <?= $priority_class ?>">
                                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                                    <div style="width:10px;height:10px;border-radius:50%;background:<?= $color ?>;"></div>
                                    <strong><?= htmlspecialchars($priority['description']) ?></strong>
                                </div>
                                <p style="color:#94a3b8;font-size:0.9rem;">
                                    Priority ID: <?= $priority['priority_id'] ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>
    </main>
    
    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('addUserModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-user-plus"></i>
                <h2>Add New User</h2>
            </div>
            <form method="POST" id="addUserForm">
                <input type="hidden" name="add_user" value="1">
                <div class="form-group">
                    <label class="form-label">Full Name <span style="color:#e74c3c;">*</span></label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address <span style="color:#e74c3c;">*</span></label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">User Type <span style="color:#e74c3c;">*</span></label>
                    <select class="form-control" name="user_type" required onchange="toggleDepartmentField(this.value)">
                        <option value="">Select type</option>
                        <option value="admin">Administrator</option>
                        <option value="faculty">Faculty</option>
                        <option value="student">Student</option>
                        <option value="club">Club Representative</option>
                    </select>
                </div>
                <div class="form-group" id="deptField" style="display:none;">
                    <label class="form-label">Department/Club Name</label>
                    <select class="form-control" name="dept" id="deptSelect">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                        <option value="other">Other (specify below)</option>
                    </select>
                    <input type="text" class="form-control" name="dept_custom" id="deptCustom" 
                           style="margin-top:0.5rem;display:none;" placeholder="Enter department/club name">
                </div>
                <div class="form-group">
                    <label class="form-label">Password <span style="color:#e74c3c;">*</span></label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password <span style="color:#e74c3c;">*</span></label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete User Confirmation Modal -->
    <div class="modal" id="deleteUserModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('deleteUserModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-trash" style="color:#e74c3c;"></i>
                <h2 style="color:#e74c3c;">Delete User</h2>
            </div>
            <form method="POST" id="deleteUserForm">
                <input type="hidden" name="delete_user" value="1">
                <input type="hidden" id="deleteUserId" name="user_id">
                <input type="hidden" id="deleteUserType" name="user_type">
                <p id="deleteMessage" style="margin-bottom:1.5rem;color:#cbd5e1;"></p>
                <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('deleteUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle sidebar
        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('visible');
            sidebar.classList.toggle('hidden');
        });
        
        function showAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
            document.getElementById('addUserForm').reset();
            document.getElementById('deptField').style.display = 'none';
            document.getElementById('deptCustom').style.display = 'none';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function toggleDepartmentField(userType) {
            const deptField = document.getElementById('deptField');
            const deptSelect = document.getElementById('deptSelect');
            const deptCustom = document.getElementById('deptCustom');
            
            if (userType === 'faculty' || userType === 'student' || userType === 'club') {
                deptField.style.display = 'block';
                deptSelect.required = true;
                deptCustom.required = false;
                
                // Set label based on user type
                const label = deptField.querySelector('.form-label');
                if (userType === 'club') {
                    label.textContent = 'Club Name';
                    deptSelect.innerHTML = '<option value="">Select Club</option><option value="other">Other (specify below)</option>';
                } else {
                    label.textContent = 'Department';
                    deptSelect.innerHTML = '<option value="">Select Department</option><?php foreach ($departments as $dept): ?><option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option><?php endforeach; ?><option value="other">Other (specify below)</option>';
                }
            } else {
                deptField.style.display = 'none';
                deptSelect.required = false;
                deptCustom.required = false;
            }
        }
        
        // Handle department selection
        document.getElementById('deptSelect').addEventListener('change', function() {
            const deptCustom = document.getElementById('deptCustom');
            if (this.value === 'other') {
                deptCustom.style.display = 'block';
                deptCustom.required = true;
            } else {
                deptCustom.style.display = 'none';
                deptCustom.required = false;
            }
        });
        
        // Validate password confirmation
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showAlert('Passwords do not match!', 'warning');
            }
            
            // If department is "other", copy custom value to dept field
            const deptSelect = this.querySelector('select[name="dept"]');
            const deptCustom = this.querySelector('input[name="dept_custom"]');
            
            if (deptSelect && deptSelect.value === 'other' && deptCustom && deptCustom.value) {
                // Create a hidden input with the custom value
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'dept';
                hiddenInput.value = deptCustom.value;
                this.appendChild(hiddenInput);
                
                // Disable the original select
                deptSelect.disabled = true;
            }
        });
        
        function deleteUser(userId, userType) {
            const userElement = document.querySelector(`.user-list-item[data-id="${userId}"][data-type="${userType}"]`);
            const userName = userElement.querySelector('div:nth-child(2) div:first-child').textContent;
            
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserType').value = userType;
            document.getElementById('deleteMessage').textContent = 
                `Are you sure you want to delete "${userName}"? This action cannot be undone.`;
            
            document.getElementById('deleteUserModal').style.display = 'flex';
        }
        
        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const icon = {
                'success': 'fa-check-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle',
                'error': 'fa-exclamation-circle'
            }[type];
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${type}`;
            alertDiv.innerHTML = `<i class="fas ${icon}"></i><div>${message}</div>`;
            container.appendChild(alertDiv);
            
            // Auto-hide alert after 5 seconds
            setTimeout(() => {
                alertDiv.style.transition = 'opacity 0.8s';
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 800);
            }, 5000);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };
        
        // Auto-hide existing alerts after 5 seconds
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

