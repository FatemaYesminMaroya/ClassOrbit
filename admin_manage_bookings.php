<?php
session_start();
/* ================= LOGOUT ================= */
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
$user_name = $_SESSION['user_name'] ?? 'System Administrator';
$user_email = $_SESSION['user_email'] ?? 'admin@classorbit.com';

/* ================= DB CONNECTION ================= */
$conn = new mysqli("localhost", "root", "", "classorbit");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ================= PROCESS ACTIONS ================= */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_booking'])) {
        $booking_id = (int) $_POST['booking_id'];
        $cancel_reason = trim($_POST['cancel_reason']);
        if (!empty($cancel_reason)) {
            $stmt = $conn->prepare("UPDATE room_booking SET status = 'cancelled', cancel_reason = ? WHERE id = ?");
            $stmt->bind_param("si", $cancel_reason, $booking_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Booking cancelled successfully!</div>";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['approve_booking'])) {
        $booking_id = (int) $_POST['booking_id'];
        $stmt = $conn->prepare("UPDATE room_booking SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Booking approved!</div>";
        $stmt->close();
    } elseif (isset($_POST['reject_booking'])) {
        $booking_id = (int) $_POST['booking_id'];
        $stmt = $conn->prepare("UPDATE room_booking SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $msg = "<div class='alert warning'><i class='fas fa-exclamation-circle'></i> Booking rejected!</div>";
        $stmt->close();
    }
}

/* ================= FETCH BOOKINGS ================= */
$sql = "
SELECT rb.id, rb.reason, rb.status, rb.created_at, rb.urgent_needs, rb.cancel_reason, rb.dept, 
       cr.room_num, c.start_time, c.end_time,
       CASE WHEN rb.priority_id = 1 THEN 'faculty' WHEN rb.priority_id = 2 THEN 'club' WHEN rb.priority_id = 3 THEN 'student' END AS user_type,
       COALESCE(f.name, s.name, cl.name) AS user_name
FROM room_booking rb
JOIN checking c ON rb.checking_id = c.id
JOIN class_room cr ON c.class_room_id = cr.id
LEFT JOIN faculty f ON rb.user_id = f.id AND rb.priority_id = 1
LEFT JOIN club cl ON rb.user_id = cl.id AND rb.priority_id = 2
LEFT JOIN student s ON rb.user_id = s.id AND rb.priority_id = 3
ORDER BY rb.created_at DESC LIMIT 200";
$bookings_result = $conn->query($sql);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings | ClassOrbit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .alert.success {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
            color: #d4edda;
        }

        .alert.warning {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid #f59e0b;
            color: #fde68a;
        }

        .section-card {
            background: #1e293b;
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid #334155;
        }

        .filters {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 180px;
        }

        .filter-select,
        .filter-input {
            padding: 0.8rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #f1f5f9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #334155;
        }

        th {
            color: #94a3b8;
            cursor: pointer;
            position: relative;
        }

        .status-pending {
            color: #f39c12;
        }

        .status-approved {
            color: #27ae60;
        }

        .status-rejected,
        .status-cancelled {
            color: #e74c3c;
        }

        .badge {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .badge-faculty {
            background: #8e44ad33;
            color: #8e44ad;
        }

        .badge-club {
            background: #e67e2233;
            color: #e67e22;
        }

        .badge-student {
            background: #3498db33;
            color: #3498db;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #1e293b;
            padding: 2rem;
            border-radius: 16px;
            width: 400px;
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
            <li><a href="admin_dashboard.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_manage_bookings.php" class="active"><i class="fas fa-calendar-check"></i> Manage Bookings</a></li>
            <li><a href="admin_manage_rooms.php"><i class="fas fa-door-closed"></i> Manage Rooms</a></li>
            <li><a href="admin_conflicts_manage.php"><i class="fas fa-exclamation-triangle"></i> Conflicts</a></li>
            <li><a href="admin_system_settings.php"><i class="fas fa-cogs"></i> System Settings</a></li>
            <li class="logout"><a href="?logout=1"><i class="fas fa-power-off"></i> Logout</a></li>
        </ul>
    </aside>

    <main>
        <div class="header">
            <h1>Manage Bookings</h1>
        </div>

        <?= $msg ?>

        <div class="section-card">
            <div class="filters">
                <div class="filter-group">
                    <label>Status</label>
                    <select class="filter-select" id="filter-status">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>User Type</label>
                    <select class="filter-select" id="filter-user-type">
                        <option value="all">All Users</option>
                        <option value="faculty">Faculty</option>
                        <option value="club">Club</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date Range</label>
                    <input type="text" class="filter-input" id="filter-date" placeholder="Select dates">
                </div>
                <button class="btn" style="background:#334155; color:white;" onclick="resetFilters()">Reset</button>
            </div>

            <table id="bookingsTable">
                <thead>
                    <tr>
                        <th data-sort="id">ID</th>
                        <th data-sort="requester">Requester</th>
                        <th data-sort="type">Type</th>
                        <th data-sort="room">Room</th>
                        <th data-sort="datetime">Date & Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bookings_result && $bookings_result->num_rows > 0):
                        while ($row = $bookings_result->fetch_assoc()): ?>
                            <tr data-id="<?= $row['id'] ?>"
                                data-type="<?= $row['user_type'] ?>"
                                data-status="<?= $row['status'] ?>"
                                data-datetime="<?= $row['start_time'] ?>">
                                <td>BK<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($row['user_name']) ?></td>
                                <td><span class="badge badge-<?= $row['user_type'] ?>"><?= ucfirst($row['user_type']) ?></span></td>
                                <td>Room <?= htmlspecialchars($row['room_num']) ?></td>
                                <td><?= date('M j, Y', strtotime($row['start_time'])) ?><br><small><?= date('g:i A', strtotime($row['start_time'])) ?></small></td>
                                <td><span class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                                <td>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="approve_booking" class="btn btn-success">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                    <button class="btn btn-danger" onclick="openCancelModal(<?= $row['id'] ?>)">Cancel</button>
                                </td>
                            </tr>
                    <?php endwhile;
                    endif; ?>
                    <tr class="no-results" style="display:none;">
                        <td colspan="7" style="text-align:center; padding:2rem;">No bookings found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal" id="cancelModal">
        <div class="modal-content">
            <h3>Cancel Booking</h3>
            <form method="POST">
                <input type="hidden" name="booking_id" id="cancel_booking_id">
                <textarea name="cancel_reason" class="filter-input" style="width:100%; margin:1rem 0;" placeholder="Reason..." required></textarea>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <button type="button" class="btn" onclick="closeCancelModal()">Back</button>
                    <button type="submit" name="cancel_booking" class="btn btn-danger">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // UI Controls
        const sidebar = document.getElementById('sidebar');
        document.getElementById('hamburgerBtn').onclick = () => sidebar.classList.toggle('visible');

        function openCancelModal(id) {
            document.getElementById('cancel_booking_id').value = id;
            document.getElementById('cancelModal').style.display = 'flex';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
        }

        // Filter Logic
        const table = document.getElementById('bookingsTable');
        const allRows = Array.from(table.querySelectorAll('tbody tr:not(.no-results)'));
        const filterStatus = document.getElementById('filter-status');
        const filterType = document.getElementById('filter-user-type');
        const filterDate = document.getElementById('filter-date');

        function filterBookings() {
            const statusVal = filterStatus.value;
            const typeVal = filterType.value;
            const dateRange = filterDate.value;

            let visibleCount = 0;

            allRows.forEach(row => {
                const rowStatus = row.dataset.status;
                const rowType = row.dataset.type;
                const rowDate = new Date(row.dataset.datetime);

                let statusMatch = (statusVal === 'all' || rowStatus === statusVal);
                let typeMatch = (typeVal === 'all' || rowType === typeVal);
                let dateMatch = true;

                if (dateRange && dateRange.includes(' to ')) {
                    const [start, end] = dateRange.split(' to ').map(d => new Date(d));
                    end.setHours(23, 59, 59);
                    dateMatch = (rowDate >= start && rowDate <= end);
                } else if (dateRange) {
                    dateMatch = rowDate.toDateString() === new Date(dateRange).toDateString();
                }

                if (statusMatch && typeMatch && dateMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            table.querySelector('.no-results').style.display = visibleCount === 0 ? 'table-row' : 'none';
        }

        // Initialize Flatpickr with Callback
        flatpickr("#filter-date", {
            mode: "range",
            dateFormat: "Y-m-d",
            onChange: filterBookings
        });

        // Add Listeners to Dropdowns
        filterStatus.onchange = filterBookings;
        filterType.onchange = filterBookings;

        function resetFilters() {
            filterStatus.value = 'all';
            filterType.value = 'all';
            const fp = document.getElementById('filter-date')._flatpickr;
            fp.clear();
            filterBookings();
        }
    </script>
</body>

</html>