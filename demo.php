<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Booking System - Manage Rooms</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --info: #17a2b8;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --sidebar-width: 250px;
            --faculty-color: #8e44ad;
            --club-color: #e67e22;
            --student-color: #3498db;
            --admin-color: #2c3e50;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary);
            color: white;
            height: 100vh;
            position: fixed;
            padding: 20px 0;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .logo {
            text-align: center;
            padding: 20px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .logo h2 {
            font-size: 1.5rem;
            color: var(--light);
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        .logo p {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 5px;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            padding: 15px 25px;
            transition: all 0.3s;
            cursor: pointer;
            display: flex;
            align-items: center;
            border-left: 4px solid transparent;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--secondary);
        }
        
        .nav-item i {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 25px;
            width: calc(100% - var(--sidebar-width));
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 1.8rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background-color: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        /* Section Styles */
        .section {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--accent);
            color: white;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }
        
        .btn-faculty {
            background-color: var(--faculty-color);
            color: white;
        }
        
        .btn-club {
            background-color: var(--club-color);
            color: white;
        }
        
        .btn-student {
            background-color: var(--student-color);
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            color: var(--primary);
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-conflict {
            background-color: #fef5e7;
            color: #7d6608;
        }
        
        .status-maintenance {
            background-color: #e8f4fc;
            color: #2c3e50;
        }
        
        .status-available {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-booked {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        
        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .facility-tag {
            display: inline-block;
            background-color: #e9ecef;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin: 2px;
            color: #495057;
        }
        
        .facilities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">
            <h2>Class<span>Book</span></h2>
            <p>Admin Dashboard</p>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_dashboard.php" style="text-decoration: none; color: white; display: flex; align-items: center;">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_manage_bookings.php" style="text-decoration: none; color: white; display: flex; align-items: center;">
                    <i class="fas fa-calendar-check"></i>
                    <span>Manage All Bookings</span>
                </a>
            </li>
            <li class="nav-item active">
                <a href="admin_manage_rooms.php" style="text-decoration: none; color: white; display: flex; align-items: center;">
                    <i class="fas fa-door-closed"></i>
                    <span>Manage Rooms</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_conflicts_manage.php" style="text-decoration: none; color: white; display: flex; align-items: center;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Conflict Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_system_settings.php" style="text-decoration: none; color: white; display: flex; align-items: center;">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 id="page-title">Manage Rooms</h1>
            <div class="user-info">
                <div class="user-avatar">AD</div>
                <div>
                    <p style="font-weight: 600;">System Administrator</p>
                    <p style="font-size: 0.8rem; color: var(--gray);">admin@university.edu</p>
                </div>
                <button class="btn btn-outline" id="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
        
        <!-- Manage Rooms Section -->
        <div class="section" id="rooms">
            <div class="section-header">
                <h2 class="section-title">Manage Rooms</h2>
                <div>
                    <button class="btn btn-primary" onclick="showAddRoomModal()">
                        <i class="fas fa-plus"></i> Add New Room
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Room No</th>
                            <th>Type</th>
                            <th>Floor</th>
                            <th>Capacity</th>
                            <th>Facilities</th>
                            <th>Status</th>
                            <th>Maintenance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rooms-table">
                        <!-- Demo data -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div class="modal" id="addRoomModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="color: var(--primary);">Add New Room</h2>
                <button class="close-modal" onclick="closeModal('addRoomModal')">&times;</button>
            </div>
            
            <form id="addRoomForm">
                <div class="form-group">
                    <label class="form-label">Room Number</label>
                    <input type="text" class="form-control" id="roomNumber" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Floor Number</label>
                    <input type="number" class="form-control" id="floorNumber" min="1" max="10" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Room Type</label>
                    <select class="form-control" id="roomType" required>
                        <option value="">Select Type</option>
                        <option value="Classroom">Classroom</option>
                        <option value="Lab">Lab</option>
                        <option value="Auditorium">Auditorium</option>
                        <option value="Conference">Conference Room</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="roomCapacity" min="10" max="200" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Facilities</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="facility-projector" value="Projector">
                            <label for="facility-projector">Projector</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="facility-ac" value="AC" checked>
                            <label for="facility-ac">Air Conditioning</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="facility-speaker" value="Speaker">
                            <label for="facility-speaker">Speaker System</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="facility-whiteboard" value="Whiteboard" checked>
                            <label for="facility-whiteboard">Whiteboard</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="facility-pc" value="PC">
                            <label for="facility-pc">Computer Lab</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="roomStatus" required>
                        <option value="available">Available</option>
                        <option value="maintenance">Under Maintenance</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes/Problems</label>
                    <textarea class="form-control" id="roomNotes" rows="3"></textarea>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addRoomModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Demo data 
        const demoData = {
            rooms: [
                { id: 101, room_number: "301", floor: 3, type: "Classroom", capacity: 40, facilities: ["Projector", "AC", "Whiteboard", "Speaker"], status: "available", maintenance: false, notes: "" },
                { id: 102, room_number: "302", floor: 3, type: "Classroom", capacity: 35, facilities: ["Projector", "AC", "Whiteboard"], status: "available", maintenance: false, notes: "" },
                { id: 103, room_number: "303", floor: 3, type: "Classroom", capacity: 50, facilities: ["Projector", "AC", "Whiteboard", "Speaker"], status: "available", maintenance: false, notes: "" },
                { id: 104, room_number: "201", floor: 2, type: "Classroom", capacity: 30, facilities: ["Projector", "AC", "Whiteboard"], status: "available", maintenance: false, notes: "" },
                { id: 105, room_number: "205", floor: 2, type: "Classroom", capacity: 25, facilities: ["AC", "Whiteboard"], status: "available", maintenance: false, notes: "Projector not working" },
                { id: 106, room_number: "101", floor: 1, type: "Classroom", capacity: 60, facilities: ["Projector", "AC", "Whiteboard", "Speaker"], status: "booked", maintenance: false, notes: "" },
                { id: 107, room_number: "Lab-1", floor: 1, type: "Lab", capacity: 25, facilities: ["PC", "Projector", "AC", "Whiteboard"], status: "available", maintenance: false, notes: "" },
                { id: 108, room_number: "Lab-3", floor: 1, type: "Lab", capacity: 30, facilities: ["PC", "Projector", "AC", "Whiteboard"], status: "booked", maintenance: false, notes: "" },
                { id: 109, room_number: "Lab-2", floor: 1, type: "Lab", capacity: 20, facilities: ["PC", "AC", "Whiteboard"], status: "maintenance", maintenance: true, notes: "Under maintenance until Nov 20" },
                { id: 110, room_number: "Conference-1", floor: 4, type: "Conference", capacity: 20, facilities: ["Projector", "AC", "Whiteboard", "Speaker"], status: "available", maintenance: false, notes: "" },
                { id: 111, room_number: "Auditorium", floor: 1, type: "Auditorium", capacity: 200, facilities: ["Projector", "AC", "Stage", "Speaker"], status: "available", maintenance: false, notes: "" }
            ]
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Populate demo data
            populateRoomsTable();
            
            // Set up form submissions
            document.getElementById('addRoomForm').addEventListener('submit', function(e) {
                e.preventDefault();
                addNewRoom();
            });
            
            // Logout button
            document.getElementById('logout-btn').addEventListener('click', function() {
                if (confirm("Are you sure you want to logout?")) {
                    alert("Logged out successfully. this redirect to login page.");
                }
            });
        });

        // Populate rooms table
        function populateRoomsTable() {
            const container = document.getElementById('rooms-table');
            let html = '';
            
            demoData.rooms.forEach(room => {
                const statusClass = room.maintenance ? 'status-maintenance' : 
                                  room.status === 'available' ? 'status-available' : 'status-booked';
                const statusText = room.maintenance ? 'Maintenance' : 
                                  room.status === 'available' ? 'Available' : 'Booked';
                
                // Generate facilities tags
                let facilitiesHtml = '';
                room.facilities.forEach(facility => {
                    facilitiesHtml += `<span class="facility-tag">${facility}</span>`;
                });
                
                html += `
                <tr>
                    <td><strong>${room.room_number}</strong></td>
                    <td>${room.type}</td>
                    <td>Floor ${room.floor}</td>
                    <td>${room.capacity} seats</td>
                    <td><div class="facilities-list">${facilitiesHtml}</div></td>
                    <td><span class="status ${statusClass}">${statusText}</span></td>
                    <td>
                        ${room.maintenance ? 
                        `<span class="status status-maintenance">Under Maintenance</span>` : 
                        `<button class="action-btn btn-warning" onclick="toggleMaintenance(${room.id})">Block for Maintenance</button>`}
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn btn-primary btn-sm" onclick="editRoom(${room.id})">Edit</button>
                            <button class="action-btn btn-outline btn-sm" onclick="viewRoom(${room.id})">View</button>
                            <button class="action-btn btn-danger btn-sm" onclick="deleteRoom(${room.id})">Delete</button>
                        </div>
                    </td>
                </tr>
                `;
            });
            
            container.innerHTML = html;
        }

        // Room management functions
        function editRoom(roomId) {
            alert(`Editing room with ID ${roomId}`);
            // this open the edit room modal
        }
        
        function toggleMaintenance(roomId) {
            if (confirm("Block this room for maintenance?")) {
                alert(`Room ${roomId} blocked for maintenance`);
                //this update the backend
                populateRoomsTable();
            }
        }
        
        function viewRoom(roomId) {
            alert(`Viewing details for room ${roomId}`);
        }
        
        function deleteRoom(roomId) {
            if (confirm("Are you sure you want to delete this room? This action cannot be undone.")) {
                alert(`Room ${roomId} deleted`);
                // this update the backend
                populateRoomsTable();
            }
        }

        // Modal functions
        function showAddRoomModal() {
            document.getElementById('addRoomModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function addNewRoom() {
            const roomNumber = document.getElementById('roomNumber').value;
            const floorNumber = document.getElementById('floorNumber').value;
            const roomType = document.getElementById('roomType').value;
            const roomCapacity = document.getElementById('roomCapacity').value;
            const roomStatus = document.getElementById('roomStatus').value;
            const roomNotes = document.getElementById('roomNotes').value;
            
            // Collect facilities
            const facilities = [];
            if (document.getElementById('facility-projector').checked) facilities.push('Projector');
            if (document.getElementById('facility-ac').checked) facilities.push('AC');
            if (document.getElementById('facility-speaker').checked) facilities.push('Speaker');
            if (document.getElementById('facility-whiteboard').checked) facilities.push('Whiteboard');
            if (document.getElementById('facility-pc').checked) facilities.push('PC');
            
            alert(`New room added:\nRoom Number: ${roomNumber}\nFloor: ${floorNumber}\nType: ${roomType}\nCapacity: ${roomCapacity}\nStatus: ${roomStatus}\nFacilities: ${facilities.join(', ')}`);
            
            // this send data to backend
            closeModal('addRoomModal');
            populateRoomsTable();
        }
    </script>
</body>
</html>