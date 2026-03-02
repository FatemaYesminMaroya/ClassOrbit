

<?php
session_start();

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

/* ================= GET BOOKING ID ================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$booking_id = (int)$_GET['id'];

/* ================= FETCH BOOKING DETAILS ================= */
$sql = "
SELECT
    rb.id, rb.reason, rb.status, rb.created_at, rb.email,
    rb.urgent_needs, rb.cancel_reason, rb.dept,
    cr.room_num, cr.floor_num, cr.capacity, cr.type_name,
    cr.projector, cr.AC as ac, cr.speaker, cr.any_prob,
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
WHERE rb.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$booking = $result->fetch_assoc();
$stmt->close();

/* ================= PROCESS ACTIONS ================= */
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ===== Approve Booking ===== */
    if (isset($_POST['approve_booking'])) {
        $update_sql = "UPDATE room_booking SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $booking_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Booking approved successfully!</div>";
            // Refresh booking data
            $booking['status'] = 'approved';
        } else {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Failed to approve booking.</div>";
        }
        $stmt->close();
    }
    
    /* ===== Reject Booking ===== */
    elseif (isset($_POST['reject_booking'])) {
        $reject_reason = trim($_POST['reject_reason'] ?? '');
        
        if (!empty($reject_reason)) {
            $update_sql = "UPDATE room_booking SET status = 'rejected', cancel_reason = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $reject_reason, $booking_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Booking rejected successfully!</div>";
                $booking['status'] = 'rejected';
                $booking['cancel_reason'] = $reject_reason;
            } else {
                $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Failed to reject booking.</div>";
            }
            $stmt->close();
        } else {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Please provide a rejection reason.</div>";
        }
    }
    
    /* ===== Cancel Booking ===== */
    elseif (isset($_POST['cancel_booking'])) {
        $cancel_reason = trim($_POST['cancel_reason'] ?? '');
        
        if (!empty($cancel_reason)) {
            $update_sql = "UPDATE room_booking SET status = 'cancelled', cancel_reason = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $cancel_reason, $booking_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Booking cancelled successfully!</div>";
                $booking['status'] = 'cancelled';
                $booking['cancel_reason'] = $cancel_reason;
            } else {
                $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Failed to cancel booking.</div>";
            }
            $stmt->close();
        } else {
            $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Please provide a cancellation reason.</div>";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Booking | ClassOrbit</title>
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

    .btn-outline {
        background: transparent;
        border: 1px solid #334155;
        color: #cbd5e1;
    }

    .btn-back {
        margin-bottom: 2rem;
    }

    .booking-card {
        background: #1e293b;
        border-radius: 12px;
        padding: 2.5rem;
        border: 1px solid #334155;
        margin-bottom: 2rem;
    }

    .booking-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #334155;
    }

    .booking-title {
        font-size: 2rem;
        color: #f8fafc;
    }

    .status {
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .status-pending {
        background: rgba(255, 243, 205, 0.2);
        color: #f39c12;
    }

    .status-approved {
        background: rgba(209, 236, 241, 0.2);
        color: #27ae60;
    }

    .status-rejected {
        background: rgba(248, 215, 218, 0.2);
        color: #e74c3c;
    }

    .status-cancelled {
        background: rgba(108, 117, 125, 0.2);
        color: #adb5bd;
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

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .detail-card {
        background: rgba(30, 41, 59, 0.7);
        border-radius: 10px;
        padding: 1.5rem;
        border: 1px solid #334155;
    }

    .detail-title {
        font-size: 1.2rem;
        color: #f8fafc;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #334155;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 0.8rem 0;
        border-bottom: 1px solid rgba(51, 65, 85, 0.3);
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        color: #94a3b8;
    }

    .detail-value {
        color: #f1f5f9;
        font-weight: 500;
    }

    .reason-box {
        background: rgba(15, 23, 42, 0.5);
        padding: 1.5rem;
        border-radius: 10px;
        margin: 1.5rem 0;
        border-left: 4px solid #f59e0b;
    }

    .reason-box h3 {
        color: #f59e0b;
        margin-bottom: 0.5rem;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #334155;
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

        .details-grid {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            flex-direction: column;
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
            <li><a href="admin_system_settings.php"><i class="fas fa-cogs"></i> System Settings</a></li>
            <li class="logout"><a href="?logout=1"><i class="fas fa-power-off"></i> Logout</a></li>
        </ul>
    </aside>
    
    <main>
        <a href="admin_dashboard.php" class="btn btn-outline btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <?= $msg ?>
        
        <div class="booking-card">
            <div class="booking-header">
                <div>
                    <h1 class="booking-title">Booking Details</h1>
                    <p style="color:#94a3b8;margin-top:0.5rem;">Booking ID: BK<?= str_pad($booking['id'], 4, '0', STR_PAD_LEFT) ?></p>
                </div>
                <span class="status status-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
            </div>
            
            <div class="reason-box">
                <h3><i class="fas fa-sticky-note"></i> Booking Reason</h3>
                <p><?= htmlspecialchars($booking['reason']) ?></p>
                <?php if (!empty($booking['urgent_needs'])): ?>
                    <div style="margin-top:1rem;padding:0.8rem;background:rgba(245,158,11,0.1);border-radius:8px;border-left:3px solid #f59e0b;">
                        <strong><i class="fas fa-exclamation-circle"></i> Special Requirements:</strong>
                        <?= htmlspecialchars($booking['urgent_needs']) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($booking['cancel_reason'])): ?>
                <div class="reason-box" style="border-left-color:#e74c3c;">
                    <h3 style="color:#e74c3c;"><i class="fas fa-times-circle"></i> Cancellation/Rejection Reason</h3>
                    <p><?= htmlspecialchars($booking['cancel_reason']) ?></p>
                </div>
            <?php endif; ?>
            
            <div class="details-grid">
                <div class="detail-card">
                    <div class="detail-title"><i class="fas fa-user"></i> Requester Information</div>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['user_name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['email']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Department:</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['dept']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">User Type:</span>
                        <span class="badge badge-<?= $booking['user_type'] ?>"><?= ucfirst($booking['user_type']) ?></span>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-title"><i class="fas fa-door-closed"></i> Room Information</div>
                    <div class="detail-row">
                        <span class="detail-label">Room Number:</span>
                        <span class="detail-value"><?= $booking['room_num'] ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Floor:</span>
                        <span class="detail-value"><?= $booking['floor_num'] ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value"><?= $booking['type_name'] ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Capacity:</span>
                        <span class="detail-value"><?= $booking['capacity'] ?> seats</span>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-title"><i class="fas fa-calendar-alt"></i> Schedule Details</div>
                    <div class="detail-row">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value"><?= date('F j, Y', strtotime($booking['start_time'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Time:</span>
                        <span class="detail-value"><?= date('g:i A', strtotime($booking['start_time'])) ?> - <?= date('g:i A', strtotime($booking['end_time'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value">
                            <?php 
                            $start = new DateTime($booking['start_time']);
                            $end = new DateTime($booking['end_time']);
                            $interval = $start->diff($end);
                            echo $interval->h . ' hours ' . $interval->i . ' minutes';
                            ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Requested On:</span>
                        <span class="detail-value"><?= date('F j, Y g:i A', strtotime($booking['created_at'])) ?></span>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-title"><i class="fas fa-tools"></i> Facilities</div>
                    <div class="detail-row">
                        <span class="detail-label">Projector:</span>
                        <span class="detail-value" style="color:<?= $booking['projector'] === 'Yes' ? '#27ae60' : '#e74c3c' ?>">
                            <?= $booking['projector'] ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Air Conditioning:</span>
                        <span class="detail-value" style="color:<?= $booking['ac'] === 'Yes' ? '#27ae60' : '#e74c3c' ?>">
                            <?= $booking['ac'] ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Speaker System:</span>
                        <span class="detail-value" style="color:<?= $booking['speaker'] === 'Yes' ? '#27ae60' : '#e74c3c' ?>">
                            <?= $booking['speaker'] ?>
                        </span>
                    </div>
                    <?php if (!empty($booking['any_prob'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Room Issues:</span>
                        <span class="detail-value" style="color:#f39c12"><?= htmlspecialchars($booking['any_prob']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (in_array($booking['status'], ['pending', 'approved'])): ?>
                <div class="action-buttons">
                    <?php if ($booking['status'] === 'pending'): ?>
                        <button type="button" class="btn btn-success" onclick="openApproveModal()">
                            <i class="fas fa-check-circle"></i> Approve Booking
                        </button>
                        <button type="button" class="btn btn-danger" onclick="openRejectModal()">
                            <i class="fas fa-times-circle"></i> Reject Booking
                        </button>
                    <?php elseif ($booking['status'] === 'approved'): ?>
                        <button type="button" class="btn btn-warning" onclick="openCancelModal()">
                            <i class="fas fa-times-circle"></i> Cancel Booking
                        </button>
                    <?php endif; ?>
                    <a href="admin_manage_bookings.php" class="btn btn-outline">
                        <i class="fas fa-list"></i> View All Bookings
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Approve Modal -->
    <div class="modal" id="approveModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('approveModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-check-circle" style="color:#27ae60"></i>
                <h2>Approve Booking</h2>
            </div>
            <form method="POST">
                <p style="color:#cbd5e1;margin-bottom:1.5rem;">Are you sure you want to approve this booking?</p>
                <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('approveModal')">Cancel</button>
                    <button type="submit" name="approve_booking" class="btn btn-success">Approve Booking</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('rejectModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-times-circle" style="color:#e74c3c"></i>
                <h2 style="color:#e74c3c">Reject Booking</h2>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Reason for rejection <span style="color:#e74c3c">*</span></label>
                    <textarea name="reject_reason" class="form-control" rows="4" placeholder="Please provide a reason for rejection..." required></textarea>
                </div>
                <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">Cancel</button>
                    <button type="submit" name="reject_booking" class="btn btn-danger">Reject Booking</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cancel Modal -->
    <div class="modal" id="cancelModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('cancelModal')">×</button>
            <div class="modal-header">
                <i class="fas fa-times-circle" style="color:#f39c12"></i>
                <h2 style="color:#f39c12">Cancel Booking</h2>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Reason for cancellation <span style="color:#e74c3c">*</span></label>
                    <textarea name="cancel_reason" class="form-control" rows="4" placeholder="Please provide a reason for cancellation..." required></textarea>
                </div>
                <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('cancelModal')">Cancel</button>
                    <button type="submit" name="cancel_booking" class="btn btn-warning">Cancel Booking</button>
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
        
        function openApproveModal() {
            document.getElementById('approveModal').style.display = 'flex';
        }
        
        function openRejectModal() {
            document.getElementById('rejectModal').style.display = 'flex';
        }
        
        function openCancelModal() {
            document.getElementById('cancelModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            if (modalId === 'rejectModal' || modalId === 'cancelModal') {
                document.querySelector(`#${modalId} textarea`).value = '';
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                const textareas = event.target.querySelectorAll('textarea');
                textareas.forEach(textarea => textarea.value = '');
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

