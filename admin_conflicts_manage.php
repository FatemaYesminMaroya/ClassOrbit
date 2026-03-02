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

/* ================= CREATE TABLES IF NOT EXISTS ================= */
// Create tables FIRST before any operations
$conn->query("
    CREATE TABLE IF NOT EXISTS detected_conflicts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        conflict_type VARCHAR(50),
        room_id INT,
        booking1_id INT,
        booking2_id INT,
        detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved BOOLEAN DEFAULT FALSE,
        resolved_at TIMESTAMP NULL,
        FOREIGN KEY (room_id) REFERENCES class_room(id),
        FOREIGN KEY (booking1_id) REFERENCES room_booking(id),
        FOREIGN KEY (booking2_id) REFERENCES room_booking(id)
    )
") or die("Error creating detected_conflicts: " . $conn->error);

$conn->query("
    CREATE TABLE IF NOT EXISTS conflict_resolutions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        conflict_id VARCHAR(50),
        action_taken VARCHAR(50),
        keep_booking_id INT NULL,
        cancel_booking_id INT NULL,
        resolution_notes TEXT,
        resolved_by INT,
        resolved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (keep_booking_id) REFERENCES room_booking(id) ON DELETE SET NULL,
        FOREIGN KEY (cancel_booking_id) REFERENCES room_booking(id) ON DELETE SET NULL,
        FOREIGN KEY (resolved_by) REFERENCES admin(id)
    )
") or die("Error creating conflict_resolutions: " . $conn->error);

/* ================= PROCESS ACTIONS ================= */
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ===== Resolve Conflict ===== */
    if (isset($_POST['resolve_conflict'])) {
        $conflict_id = $_POST['conflict_id'];
        $action = $_POST['resolution_action'];
        $keep_booking_id = !empty($_POST['keep_booking_id']) ? (int)$_POST['keep_booking_id'] : NULL;
        $cancel_booking_id = !empty($_POST['cancel_booking_id']) ? (int)$_POST['cancel_booking_id'] : NULL;
        $resolution_notes = $conn->real_escape_string($_POST['resolution_notes'] ?? '');
        
        // First, update detected_conflicts table to mark as resolved
        $conflict_db_id = str_replace('CF', '', $conflict_id);
        $update_conflict = $conn->prepare("
            UPDATE detected_conflicts 
            SET resolved = 1, resolved_at = NOW()
            WHERE id = ?
        ");
        $update_conflict->bind_param("i", $conflict_db_id);
        
        if ($update_conflict->execute()) {
            // Now insert into conflict_resolutions
            $stmt = $conn->prepare("
                INSERT INTO conflict_resolutions 
                (conflict_id, action_taken, keep_booking_id, cancel_booking_id, resolution_notes, resolved_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssiisi", $conflict_id, $action, $keep_booking_id, $cancel_booking_id, $resolution_notes, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                // Update booking status if cancelled
                if ($cancel_booking_id > 0) {
                    $cancel_stmt = $conn->prepare("
                        UPDATE room_booking 
                        SET status = 'cancelled', 
                            cancel_reason = CONCAT('Conflict resolution: ', ?)
                        WHERE id = ?
                    ");
                    $cancel_stmt->bind_param("si", $resolution_notes, $cancel_booking_id);
                    $cancel_stmt->execute();
                    $cancel_stmt->close();
                }
                
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Conflict resolved successfully!</div>";
            } else {
                $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Failed to resolve conflict: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Failed to mark conflict as resolved.</div>";
        }
        $update_conflict->close();
    }
    
    /* ===== Run Conflict Detection ===== */
    elseif (isset($_POST['run_detection'])) {
        // Detect overlapping bookings
        $detection_query = "
            INSERT INTO detected_conflicts (conflict_type, room_id, booking1_id, booking2_id, detected_at)
            SELECT 
                'Time Overlap' as conflict_type,
                c1.class_room_id as room_id,
                rb1.id as booking1_id,
                rb2.id as booking2_id,
                NOW() as detected_at
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
                AND NOT EXISTS (
                    SELECT 1 FROM detected_conflicts dc 
                    WHERE (dc.booking1_id = rb1.id AND dc.booking2_id = rb2.id)
                       OR (dc.booking1_id = rb2.id AND dc.booking2_id = rb1.id)
                )
            UNION
            SELECT 
                'Priority Conflict' as conflict_type,
                c1.class_room_id as room_id,
                rb1.id as booking1_id,
                rb2.id as booking2_id,
                NOW() as detected_at
            FROM room_booking rb1
            JOIN room_booking rb2 ON rb1.id < rb2.id
            JOIN checking c1 ON rb1.checking_id = c1.id
            JOIN checking c2 ON rb2.checking_id = c2.id
            WHERE c1.class_room_id = c2.class_room_id
                AND rb1.status = 'approved'
                AND rb2.status = 'approved'
                AND rb1.priority_id != rb2.priority_id
                AND (
                    (c1.start_time < c2.end_time AND c1.end_time > c2.start_time)
                )
                AND NOT EXISTS (
                    SELECT 1 FROM detected_conflicts dc 
                    WHERE (dc.booking1_id = rb1.id AND dc.booking2_id = rb2.id)
                       OR (dc.booking1_id = rb2.id AND dc.booking2_id = rb1.id)
                )
        ";
        
        // First, clean up old unresolved conflicts
        $conn->query("DELETE FROM detected_conflicts WHERE resolved = 0 AND detected_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        
        // Run detection
        $result = $conn->query($detection_query);
        
        if ($result) {
            $affected_rows = $conn->affected_rows;
            $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Conflict detection completed! Found $affected_rows new conflicts.</div>";
        } else {
            $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Error running conflict detection: " . $conn->error . "</div>";
        }
    }
    
    /* ===== Auto-resolve Conflicts ===== */
    elseif (isset($_POST['auto_resolve'])) {
        // Auto-resolve based on priority (higher priority wins)
        $auto_resolve_query = "
            UPDATE room_booking rb_low
            JOIN detected_conflicts dc ON rb_low.id = dc.booking2_id
            JOIN room_booking rb_high ON rb_high.id = dc.booking1_id
            SET rb_low.status = 'cancelled',
                rb_low.cancel_reason = 'Auto-resolved: Priority conflict with higher priority booking'
            WHERE dc.conflict_type = 'Priority Conflict'
                AND dc.resolved = 0
                AND rb_high.priority_id < rb_low.priority_id
                AND rb_high.status = 'approved'
                AND rb_low.status = 'approved'
        ";
        
        $conn->query("START TRANSACTION");
        
        // Mark conflicts as resolved
        $mark_resolved = $conn->query("
            UPDATE detected_conflicts 
            SET resolved = 1, resolved_at = NOW()
            WHERE resolved = 0 
            AND conflict_type = 'Priority Conflict'
        ");
        
        $auto_resolve_result = $conn->query($auto_resolve_query);
        $affected_rows = $conn->affected_rows;
        
        if ($mark_resolved && $auto_resolve_result) {
            // Insert auto-resolutions into conflict_resolutions
            $get_auto_conflicts = $conn->query("
                SELECT CONCAT('CF', LPAD(dc.id, 4, '0')) as conflict_id, 
                       dc.booking1_id, dc.booking2_id
                FROM detected_conflicts dc
                WHERE dc.resolved = 1 
                AND dc.resolved_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");
            
            while ($conflict = $get_auto_conflicts->fetch_assoc()) {
                $insert_resolution = $conn->prepare("
                    INSERT INTO conflict_resolutions 
                    (conflict_id, action_taken, keep_booking_id, cancel_booking_id, resolution_notes, resolved_by)
                    VALUES (?, 'auto_resolved', ?, ?, 'Auto-resolved based on priority rules', ?)
                ");
                $insert_resolution->bind_param("siii", 
                    $conflict['conflict_id'],
                    $conflict['booking1_id'],
                    $conflict['booking2_id'],
                    $_SESSION['user_id']
                );
                $insert_resolution->execute();
                $insert_resolution->close();
            }
            
            $conn->commit();
            $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Auto-resolved $affected_rows priority conflicts!</div>";
        } else {
            $conn->rollback();
            $msg = "<div class='alert warning'><i class='fas fa-exclamation-triangle'></i> Error during auto-resolution: " . $conn->error . "</div>";
        }
    }
}

/* ================= FETCH CONFLICT DATA ================= */

// Get active conflicts
$conflicts_query = "
    SELECT 
        CONCAT('CF', LPAD(dc.id, 4, '0')) as conflict_id,
        dc.id as db_id,
        dc.conflict_type,
        dc.detected_at,
        dc.resolved,
        cr.room_num,
        c1.start_time as booking1_start,
        c1.end_time as booking1_end,
        c2.start_time as booking2_start,
        c2.end_time as booking2_end,
        rb1.id as booking1_id,
        rb2.id as booking2_id,
        rb1.reason as booking1_reason,
        rb2.reason as booking2_reason,
        rb1.priority_id as booking1_priority,
        rb2.priority_id as booking2_priority,
        COALESCE(f1.name, s1.name, cl1.name) as booking1_user,
        COALESCE(f2.name, s2.name, cl2.name) as booking2_user,
        CASE 
            WHEN rb1.priority_id = 1 THEN 'faculty'
            WHEN rb1.priority_id = 2 THEN 'club'
            WHEN rb1.priority_id = 3 THEN 'student'
        END as booking1_type,
        CASE 
            WHEN rb2.priority_id = 1 THEN 'faculty'
            WHEN rb2.priority_id = 2 THEN 'club'
            WHEN rb2.priority_id = 3 THEN 'student'
        END as booking2_type
    FROM detected_conflicts dc
    JOIN class_room cr ON dc.room_id = cr.id
    JOIN room_booking rb1 ON dc.booking1_id = rb1.id
    JOIN room_booking rb2 ON dc.booking2_id = rb2.id
    JOIN checking c1 ON rb1.checking_id = c1.id
    JOIN checking c2 ON rb2.checking_id = c2.id
    LEFT JOIN faculty f1 ON rb1.user_id = f1.id AND rb1.priority_id = 1
    LEFT JOIN student s1 ON rb1.user_id = s1.id AND rb1.priority_id = 3
    LEFT JOIN club cl1 ON rb1.user_id = cl1.id AND rb1.priority_id = 2
    LEFT JOIN faculty f2 ON rb2.user_id = f2.id AND rb2.priority_id = 1
    LEFT JOIN student s2 ON rb2.user_id = s2.id AND rb2.priority_id = 3
    LEFT JOIN club cl2 ON rb2.user_id = cl2.id AND rb2.priority_id = 2
    WHERE dc.resolved = 0
    ORDER BY dc.detected_at DESC
";

$conflicts_result = $conn->query($conflicts_query);
$active_conflicts = [];
$resolved_conflicts_count = 0;
$time_overlaps_count = 0;
$priority_conflicts_count = 0;

if ($conflicts_result && $conflicts_result->num_rows > 0) {
    while ($conflict = $conflicts_result->fetch_assoc()) {
        $active_conflicts[] = $conflict;
        
        // Update counts
        if (strpos($conflict['conflict_type'], 'Time') !== false || strpos($conflict['conflict_type'], 'Overlap') !== false) {
            $time_overlaps_count++;
        }
        if (strpos($conflict['conflict_type'], 'Priority') !== false) {
            $priority_conflicts_count++;
        }
    }
}

// Get resolved conflicts count (this week)
$resolved_query = "
    SELECT COUNT(*) as count 
    FROM detected_conflicts 
    WHERE resolved = 1 
    AND resolved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";
$resolved_result = $conn->query($resolved_query);
if ($resolved_result && $resolved_result->num_rows > 0) {
    $resolved_data = $resolved_result->fetch_assoc();
    $resolved_conflicts_count = $resolved_data['count'];
}

// Get auto-resolved conflicts
$auto_resolved_query = "
    SELECT COUNT(DISTINCT cr.conflict_id) as count
    FROM conflict_resolutions cr
    WHERE cr.action_taken = 'auto_resolved'
    AND cr.resolved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";
$auto_resolved_result = $conn->query($auto_resolved_query);
if ($auto_resolved_result && $auto_resolved_result->num_rows > 0) {
    $auto_resolved_data = $auto_resolved_result->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conflict Management | ClassOrbit</title>
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

        .btn-warning {
            background: linear-gradient(145deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(145deg, #e74c3c, #c0392b);
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .conflict-item {
            background: rgba(254, 245, 231, 0.05);
            border-left: 4px solid #f39c12;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
            border: 1px solid #334155;
        }

        .conflict-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .conflict-booking {
            background: rgba(15, 23, 42, 0.5);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #334155;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
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

        .badge-resolved {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        .badge-pending {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }

        .badge-time {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .badge-priority {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .priority-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .priority-high {
            background: #e74c3c;
        }

        .priority-medium {
            background: #f39c12;
        }

        .priority-low {
            background: #27ae60;
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
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-info p {
            color: #94a3b8;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .icon-conflicts {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }

        .icon-resolved {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        .icon-overlaps {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .icon-priority {
            background: rgba(142, 68, 173, 0.2);
            color: #8e44ad;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
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
            max-width: 600px;
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

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-top: 0.8rem;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #cbd5e1;
        }

        .radio-item input[type="radio"] {
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
                padding: 140px 1.5rem 3rem;
            }

            .nav-container {
                padding: 0 1.5rem;
            }

            .conflict-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .conflict-booking {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
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
            <li><a href="admin_conflicts_manage.php" class="active"><i class="fas fa-exclamation-triangle"></i> Conflict Management</a></li>
            <li><a href="admin_system_settings.php"><i class="fas fa-cogs"></i> System Settings</a></li>
            <li class="logout"><a href="?logout=1"><i class="fas fa-power-off"></i> Logout</a></li>
        </ul>
    </aside>
    
    <main>
        <div class="header">
            <h1>Conflict Management</h1>
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
                    <h3 id="activeConflicts"><?= count($active_conflicts) ?></h3>
                    <p>Active Conflicts</p>
                </div>
                <div class="stat-icon icon-conflicts"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3 id="resolvedConflicts"><?= $resolved_conflicts_count ?></h3>
                    <p>Resolved This Week</p>
                </div>
                <div class="stat-icon icon-resolved"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3 id="timeOverlaps"><?= $time_overlaps_count ?></h3>
                    <p>Time Overlaps</p>
                </div>
                <div class="stat-icon icon-overlaps"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3 id="priorityConflicts"><?= $priority_conflicts_count ?></h3>
                    <p>Priority Conflicts</p>
                </div>
                <div class="stat-icon icon-priority"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
        
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Detected Conflicts</h2>
                <div class="action-buttons">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="run_detection" value="1">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-search"></i> Run Detection
                        </button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Auto-resolve conflicts based on priority rules?');">
                        <input type="hidden" name="auto_resolve" value="1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-robot"></i> Auto-resolve
                        </button>
                    </form>
                </div>
            </div>
            
            <div id="conflictsList">
                <?php if (count($active_conflicts) > 0): ?>
                    <?php foreach ($active_conflicts as $conflict): ?>
                        <?php
                        // Format dates and times
                        $detected_date = date('M j, Y', strtotime($conflict['detected_at']));
                        $booking1_time = date('g:i A', strtotime($conflict['booking1_start'])) . ' - ' . date('g:i A', strtotime($conflict['booking1_end']));
                        $booking2_time = date('g:i A', strtotime($conflict['booking2_start'])) . ' - ' . date('g:i A', strtotime($conflict['booking2_end']));
                        $booking1_date = date('M j', strtotime($conflict['booking1_start']));
                        $booking2_date = date('M j', strtotime($conflict['booking2_start']));
                        
                        // Determine priority classes
                        $booking1_priority_class = '';
                        $booking2_priority_class = '';
                        if ($conflict['booking1_priority'] == 1) $booking1_priority_class = 'priority-high';
                        elseif ($conflict['booking1_priority'] == 2) $booking1_priority_class = 'priority-medium';
                        else $booking1_priority_class = 'priority-low';
                        
                        if ($conflict['booking2_priority'] == 1) $booking2_priority_class = 'priority-high';
                        elseif ($conflict['booking2_priority'] == 2) $booking2_priority_class = 'priority-medium';
                        else $booking2_priority_class = 'priority-low';
                        
                        // Determine badge classes
                        $booking1_badge_class = 'badge-' . $conflict['booking1_type'];
                        $booking2_badge_class = 'badge-' . $conflict['booking2_type'];
                        
                        // Conflict type badge
                        $conflict_type_badge_class = strpos($conflict['conflict_type'], 'Priority') !== false ? 'badge-priority' : 'badge-time';
                        ?>
                        <div class="conflict-item">
                            <div class="conflict-header">
                                <div>
                                    <strong><?= htmlspecialchars($conflict['conflict_type']) ?>: Room <?= htmlspecialchars($conflict['room_num']) ?></strong>
                                    <div style="color:#94a3b8;font-size:0.9rem;margin-top:0.5rem;">
                                        Detected: <?= $detected_date ?> • 
                                        <span class="badge <?= $conflict_type_badge_class ?>"><?= htmlspecialchars($conflict['conflict_type']) ?></span>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-primary btn-sm" onclick="showResolveModal('<?= $conflict['conflict_id'] ?>', <?= $conflict['booking1_id'] ?>, '<?= $conflict['booking1_user'] ?>', <?= $conflict['booking2_id'] ?>, '<?= $conflict['booking2_user'] ?>')">
                                        <i class="fas fa-handshake"></i> Resolve
                                    </button>
                                    <button class="btn btn-outline btn-sm" onclick="viewConflict('<?= $conflict['conflict_id'] ?>')">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </div>
                            </div>
                            <div>
                                <div style="font-weight:600;margin-bottom:0.8rem;">Conflicting Bookings:</div>
                                
                                <!-- Booking 1 -->
                                <div class="conflict-booking">
                                    <div>
                                        <strong>BK<?= str_pad($conflict['booking1_id'], 4, '0', STR_PAD_LEFT) ?>: <?= htmlspecialchars($conflict['booking1_user']) ?></strong>
                                        <div style="color:#94a3b8;font-size:0.85rem;">
                                            <?= $booking1_date ?> • <?= $booking1_time ?><br>
                                            <?= htmlspecialchars($conflict['booking1_reason']) ?>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:1rem;">
                                        <div style="display:flex;align-items:center;">
                                            <div class="priority-dot <?= $booking1_priority_class ?>"></div>
                                            <span style="font-size:0.85rem;">
                                                <?= $conflict['booking1_priority'] == 1 ? 'High' : ($conflict['booking1_priority'] == 2 ? 'Medium' : 'Low') ?>
                                            </span>
                                        </div>
                                        <span class="badge <?= $booking1_badge_class ?>"><?= ucfirst($conflict['booking1_type']) ?></span>
                                    </div>
                                </div>
                                
                                <!-- Booking 2 -->
                                <div class="conflict-booking">
                                    <div>
                                        <strong>BK<?= str_pad($conflict['booking2_id'], 4, '0', STR_PAD_LEFT) ?>: <?= htmlspecialchars($conflict['booking2_user']) ?></strong>
                                        <div style="color:#94a3b8;font-size:0.85rem;">
                                            <?= $booking2_date ?> • <?= $booking2_time ?><br>
                                            <?= htmlspecialchars($conflict['booking2_reason']) ?>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:1rem;">
                                        <div style="display:flex;align-items:center;">
                                            <div class="priority-dot <?= $booking2_priority_class ?>"></div>
                                            <span style="font-size:0.85rem;">
                                                <?= $conflict['booking2_priority'] == 1 ? 'High' : ($conflict['booking2_priority'] == 2 ? 'Medium' : 'Low') ?>
                                            </span>
                                        </div>
                                        <span class="badge <?= $booking2_badge_class ?>"><?= ucfirst($conflict['booking2_type']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p id="noConflictsMessage" style="text-align:center;padding:2rem;color:#94a3b8;">
                        No active conflicts detected. Run conflict detection to find potential issues.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Resolve Conflict Modal -->
    <div class="modal" id="resolveModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('resolveModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-handshake"></i>
                <h2>Resolve Conflict</h2>
            </div>
            <form method="POST" id="resolveForm">
                <input type="hidden" name="resolve_conflict" value="1">
                <input type="hidden" id="conflictId" name="conflict_id">
                
                <div class="form-group">
                    <label class="form-label">Resolution Action</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="keepFirst" name="resolution_action" value="keep_first" checked>
                            <label for="keepFirst">
                                <span id="keepFirstLabel"></span> (Higher Priority)
                            </label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="keepSecond" name="resolution_action" value="keep_second">
                            <label for="keepSecond">
                                <span id="keepSecondLabel"></span>
                            </label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="cancelBoth" name="resolution_action" value="cancel_both">
                            <label for="cancelBoth">Cancel Both Bookings</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="reschedule" name="resolution_action" value="reschedule">
                            <label for="reschedule">Reschedule (Manual)</label>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" id="keepBookingId" name="keep_booking_id" value="">
                <input type="hidden" id="cancelBookingId" name="cancel_booking_id" value="">
                
                <div class="form-group">
                    <label class="form-label">Resolution Notes</label>
                    <textarea name="resolution_notes" class="form-control" rows="3" placeholder="Add notes about this resolution..." required></textarea>
                </div>
                
                <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('resolveModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Resolution</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Conflict Details Modal -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('detailsModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-info-circle"></i>
                <h2 id="detailsTitle">Conflict Details</h2>
            </div>
            <div id="detailsContent" style="color:#cbd5e1;padding:1rem 0;"></div>
        </div>
    </div>
    
    <script>
        // Toggle sidebar
        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('visible');
            sidebar.classList.toggle('hidden');
        });
        
        let currentConflictData = null;
        
        function showResolveModal(conflictId, booking1Id, booking1User, booking2Id, booking2User) {
            currentConflictData = {
                conflictId,
                booking1Id,
                booking1User,
                booking2Id,
                booking2User
            };
            
            document.getElementById('conflictId').value = conflictId;
            document.getElementById('keepFirstLabel').textContent = `Keep ${booking1User}`;
            document.getElementById('keepSecondLabel').textContent = `Keep ${booking2User}`;
            
            // Default: keep first booking (higher priority)
            document.getElementById('keepBookingId').value = booking1Id;
            document.getElementById('cancelBookingId').value = booking2Id;
            
            document.getElementById('resolveModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Update hidden fields based on radio selection
        document.querySelectorAll('input[name="resolution_action"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (!currentConflictData) return;
                
                switch(this.value) {
                    case 'keep_first':
                        document.getElementById('keepBookingId').value = currentConflictData.booking1Id;
                        document.getElementById('cancelBookingId').value = currentConflictData.booking2Id;
                        break;
                    case 'keep_second':
                        document.getElementById('keepBookingId').value = currentConflictData.booking2Id;
                        document.getElementById('cancelBookingId').value = currentConflictData.booking1Id;
                        break;
                    case 'cancel_both':
                        document.getElementById('keepBookingId').value = '';
                        document.getElementById('cancelBookingId').value = '';
                        break;
                    case 'reschedule':
                        document.getElementById('keepBookingId').value = '';
                        document.getElementById('cancelBookingId').value = '';
                        break;
                }
            });
        });
        
        function viewConflict(conflictId) {
            // Find the conflict in the PHP data
            const conflictElement = document.querySelector(`.conflict-item:has(.action-buttons button[onclick*="${conflictId}"])`);
            if (!conflictElement) return;
            
            const conflictType = conflictElement.querySelector('strong').textContent;
            const roomInfo = conflictElement.querySelector('div[style*="color:#94a3b8"]').textContent;
            const bookings = conflictElement.querySelectorAll('.conflict-booking');
            
            let content = `
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="color: #f59e0b; margin-bottom: 0.5rem;">${conflictType}</h3>
                    <p style="color: #94a3b8;">${roomInfo}</p>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem;">Booking Details:</h4>
            `;
            
            bookings.forEach((booking, index) => {
                const bookingText = booking.querySelector('strong').textContent;
                const details = booking.querySelector('div[style*="color:#94a3b8"]').textContent;
                const priority = booking.querySelector('.priority-dot').nextElementSibling.textContent;
                const userType = booking.querySelector('.badge').textContent;
                
                content += `
                    <div style="background: rgba(15,23,42,0.5); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <strong>Booking ${index + 1}: ${bookingText}</strong>
                        <div style="color:#94a3b8;font-size:0.9rem;margin-top:0.5rem;">${details}</div>
                        <div style="display:flex;gap:1rem;margin-top:0.5rem;">
                            <span>Priority: ${priority}</span>
                            <span>User Type: ${userType}</span>
                        </div>
                    </div>
                `;
            });
            
            content += `</div>`;
            
            document.getElementById('detailsTitle').textContent = `Conflict ${conflictId} Details`;
            document.getElementById('detailsContent').innerHTML = content;
            document.getElementById('detailsModal').style.display = 'flex';
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.8s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 800);
            });
        }, 5000);
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };
    </script>
</body>
</html>