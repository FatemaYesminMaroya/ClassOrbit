<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Booking System - Admin</title>
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
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .booking-icon {
            background-color: var(--secondary);
        }
        
        .room-icon {
            background-color: var(--success);
        }
        
        .conflict-icon {
            background-color: var(--warning);
        }
        
        .user-icon {
            background-color: var(--info);
        }
        
        .card-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .card-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        /* Section Styles */
        .section {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: none;
        }
        
        .section.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        .badge-faculty {
            background-color: rgba(142, 68, 173, 0.2);
            color: var(--faculty-color);
        }
        
        .badge-club {
            background-color: rgba(230, 126, 34, 0.2);
            color: var(--club-color);
        }
        
        .badge-student {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--student-color);
        }
        
        .badge-admin {
            background-color: rgba(44, 62, 80, 0.2);
            color: var(--admin-color);
        }
        
        /* Filter Styles */
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-label {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .filter-select, .filter-input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 180px;
        }
        
        /* Conflict Management */
        .conflict-item {
            background-color: #fef5e7;
            border-left: 4px solid var(--warning);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 0 5px 5px 0;
        }
        
        .conflict-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .conflict-booking {
            display: flex;
            justify-content: space-between;
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            align-items: center;
            border: 1px solid #eee;
        }
        
        .priority-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .priority-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .priority-high {
            background-color: var(--accent);
        }
        
        .priority-medium {
            background-color: var(--warning);
        }
        
        .priority-low {
            background-color: var(--success);
        }
        
        /* System Settings */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .setting-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        
        .user-list-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-avatar-small {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
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
            <li class="nav-item active" data-target="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </li>
            <li class="nav-item" data-target="bookings">
                <i class="fas fa-calendar-check"></i>
                <span>Manage All Bookings</span>
            </li>
            <li class="nav-item" data-target="rooms">
                <i class="fas fa-door-closed"></i>
                <span>Manage Rooms</span>
            </li>
            <li class="nav-item" data-target="conflicts">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Conflict Management</span>
            </li>
            <li class="nav-item" data-target="settings">
                <i class="fas fa-cog"></i>
                <span>System Settings</span>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 id="page-title">Admin Dashboard</h1>
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
        
        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-value" id="pending-bookings-count">18</div>
                        <div class="card-label">Pending Bookings</div>
                    </div>
                    <div class="card-icon booking-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-value" id="total-rooms-count">42</div>
                        <div class="card-label">Total Rooms</div>
                    </div>
                    <div class="card-icon room-icon">
                        <i class="fas fa-door-closed"></i>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-value" id="active-conflicts-count">5</div>
                        <div class="card-label">Active Conflicts</div>
                    </div>
                    <div class="card-icon conflict-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-value" id="total-users-count">287</div>
                        <div class="card-label">System Users</div>
                    </div>
                    <div class="card-icon user-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Section -->
        <div class="section active" id="dashboard">
            <div class="section-header">
                <h2 class="section-title">System Overview</h2>
                <div>
                    <span style="color: var(--gray); font-size: 0.9rem;">Last updated: Today, 11:45 AM</span>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Recent Booking Requests</th>
                            <th>Requested By</th>
                            <th>Room</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="recent-bookings">
                        <!-- Demo data -->
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px;">
                <h3 style="margin-bottom: 15px; color: var(--primary);">Quick Actions</h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="showSection('bookings')">
                        <i class="fas fa-calendar-check"></i> Review Pending Bookings
                    </button>
                    <button class="btn btn-success" onclick="showAddRoomModal()">
                        <i class="fas fa-plus"></i> Add New Room
                    </button>
                    <button class="btn btn-warning" onclick="showSection('conflicts')">
                        <i class="fas fa-exclamation-triangle"></i> Resolve Conflicts
                    </button>
                    <button class="btn btn-outline" onclick="showSection('settings')">
                        <i class="fas fa-user-cog"></i> Manage Users
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Manage Bookings Section -->
        <div class="section" id="bookings">
            <div class="section-header">
                <h2 class="section-title">Manage All Bookings</h2>
                <div>
                    <button class="btn btn-primary" onclick="showAddBookingModal()">
                        <i class="fas fa-plus"></i> Add Manual Booking
                    </button>
                </div>
            </div>
            
            <div class="filters">
                <div class="filter-group">
                    <div class="filter-label">Status</div>
                    <select class="filter-select" id="filter-status" onchange="filterBookings()">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <div class="filter-label">User Type</div>
                    <select class="filter-select" id="filter-user-type" onchange="filterBookings()">
                        <option value="all">All Users</option>
                        <option value="faculty">Faculty</option>
                        <option value="club">Club</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <div class="filter-label">Date Range</div>
                    <input type="text" class="filter-input" id="filter-date" placeholder="Select date range" onchange="filterBookings()">
                </div>
                
                <button class="btn btn-outline" onclick="resetFilters()" style="align-self: flex-end;">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Requester</th>
                            <th>User Type</th>
                            <th>Room</th>
                            <th>Date & Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookings-table">
                        <!-- Demo data -->
                    </tbody>
                </table>
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
        
        <!-- Conflict Management Section -->
        <div class="section" id="conflicts">
            <div class="section-header">
                <h2 class="section-title">Conflict Management</h2>
                <div>
                    <button class="btn btn-warning" onclick="runConflictDetection()">
                        <i class="fas fa-search"></i> Run Detection
                    </button>
                    <button class="btn btn-primary" style="margin-left: 10px;" onclick="autoResolveConflicts()">
                        <i class="fas fa-robot"></i> Auto-resolve
                    </button>
                </div>
            </div>
            
            <div id="conflicts-list">
                <!-- Demo conflicts -->
            </div>
        </div>
        
        <!-- System Settings Section -->
        <div class="section" id="settings">
            <div class="section-header">
                <h2 class="section-title">System Settings</h2>
                <div>
                    <button class="btn btn-primary" onclick="saveSettings()">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </div>
            
            <div class="settings-grid">
                <div class="setting-card">
                    <h3 style="margin-bottom: 15px; color: var(--primary);">
                        <i class="fas fa-users"></i> Manage User Accounts
                    </h3>
                    <div id="users-list">
                        <!-- Demo users -->
                    </div>
                    <button class="btn btn-outline" style="margin-top: 15px; width: 100%;" onclick="showAddUserModal()">
                        <i class="fas fa-user-plus"></i> Add New User
                    </button>
                </div>
                
                <div class="setting-card">
                    <h3 style="margin-bottom: 15px; color: var(--primary);">
                        <i class="fas fa-sliders-h"></i> Configure System Rules
                    </h3>
                    <div class="form-group">
                        <label class="form-label">Maximum Booking Duration (Hours)</label>
                        <input type="number" class="form-control" id="max-booking-hours" value="4">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Advance Booking Limit (Days)</label>
                        <input type="number" class="form-control" id="advance-booking-days" value="30">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Auto-cancel Unapproved Bookings After (Hours)</label>
                        <input type="number" class="form-control" id="auto-cancel-hours" value="48">
                    </div>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="auto-approve-faculty" checked>
                            <label for="auto-approve-faculty">Auto-approve faculty bookings</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="conflict-alerts" checked>
                            <label for="conflict-alerts">Send conflict alerts to users</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="overtime-requests" checked>
                            <label for="overtime-requests">Allow overtime requests</label>
                        </div>
                    </div>
                </div>
                
                <div class="setting-card">
                    <h3 style="margin-bottom: 15px; color: var(--primary);">
                        <i class="fas fa-chart-line"></i> Priority System
                    </h3>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; margin-bottom: 10px;">
                            <div class="priority-dot priority-high"></div>
                            <span style="margin-left: 10px; font-weight: 600;">Faculty - Highest Priority</span>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--gray);">Faculty bookings are automatically approved and override conflicts</p>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; margin-bottom: 10px;">
                            <div class="priority-dot priority-medium"></div>
                            <span style="margin-left: 10px; font-weight: 600;">Club - Medium Priority</span>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--gray);">Club bookings check for faculty conflicts first</p>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; margin-bottom: 10px;">
                            <div class="priority-dot priority-low"></div>
                            <span style="margin-left: 10px; font-weight: 600;">Student - Standard Priority</span>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--gray);">Student bookings check for faculty and club conflicts</p>
                    </div>
                </div>
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

    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="color: var(--primary);">Add New User</h2>
                <button class="close-modal" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            
            <form id="addUserForm">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="userName" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="userEmail" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">User Type</label>
                    <select class="form-control" id="userType" required>
                        <option value="">Select User Type</option>
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="club">Club Representative</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Department/Club</label>
                    <input type="text" class="form-control" id="userDept">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" id="userPassword" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="userConfirmPassword" required>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Booking Modal -->
    <div class="modal" id="addBookingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="color: var(--primary);">Add Manual Booking</h2>
                <button class="close-modal" onclick="closeModal('addBookingModal')">&times;</button>
            </div>
            
            <form id="addBookingForm">
                <div class="form-group">
                    <label class="form-label">Select User</label>
                    <select class="form-control" id="bookingUser" required>
                        <option value="">Select User</option>
                        <!-- Users will be populated by JavaScript -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Room</label>
                    <select class="form-control" id="bookingRoom" required>
                        <option value="">Select Room</option>
                        <!-- Rooms will be populated by JavaScript -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Booking Date</label>
                    <input type="date" class="form-control" id="bookingDate" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="bookingStartTime" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">End Time</label>
                    <input type="time" class="form-control" id="bookingEndTime" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason for Booking</label>
                    <select class="form-control" id="bookingReason" required>
                        <option value="">Select Reason</option>
                        <option value="Class">Regular Class</option>
                        <option value="Extra Class">Extra Class</option>
                        <option value="Club Event">Club Event</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Exam">Exam</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Additional Details</label>
                    <textarea class="form-control" id="bookingDetails" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="bookingStatus" required>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addBookingModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Booking</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Demo data 
        const demoData = {
            bookings: [
                { id: 1001, user_id: 201, user_name: "Dr. Sarah Johnson", user_type: "faculty", user_dept: "Computer Science", room_id: 101, room_number: "301", date: "2023-11-15", start_time: "09:00", end_time: "11:00", reason: "Data Structures Class", details: "Regular class for CS201 students", status: "approved", created_at: "2023-11-10 14:30:00" },
                { id: 1002, user_id: 102, user_name: "John Smith", user_type: "student", user_dept: "Engineering", room_id: 105, room_number: "205", date: "2023-11-16", start_time: "14:00", end_time: "16:00", reason: "Study Group", details: "Group study for calculus exam", status: "pending", created_at: "2023-11-11 10:15:00" },
                { id: 1003, user_id: 305, user_name: "Robotics Club", user_type: "club", user_dept: "Robotics Club", room_id: 108, room_number: "Lab-3", date: "2023-11-18", start_time: "16:00", end_time: "18:00", reason: "Club Event", details: "Weekly robotics workshop", status: "approved", created_at: "2023-11-09 16:45:00" },
                { id: 1004, user_id: 202, user_name: "Prof. Michael Chen", user_type: "faculty", user_dept: "Mathematics", room_id: 101, room_number: "301", date: "2023-11-15", start_time: "11:00", end_time: "13:00", reason: "Calculus Class", details: "Advanced calculus for math majors", status: "approved", created_at: "2023-11-08 09:20:00" },
                { id: 1005, user_id: 103, user_name: "Emma Wilson", user_type: "student", user_dept: "Business", room_id: 110, room_number: "Conference-1", date: "2023-11-20", start_time: "10:00", end_time: "12:00", reason: "Team Meeting", details: "Group project meeting for business class", status: "pending", created_at: "2023-11-12 13:10:00" },
                { id: 1006, user_id: 306, user_name: "Drama Club", user_type: "club", user_dept: "Drama Club", room_id: 115, room_number: "Auditorium", date: "2023-11-25", start_time: "18:00", end_time: "21:00", reason: "Club Event", details: "Annual drama club performance rehearsal", status: "approved", created_at: "2023-11-07 11:05:00" },
                { id: 1007, user_id: 104, user_name: "Alex Rivera", user_type: "student", user_dept: "Computer Science", room_id: 108, room_number: "Lab-3", date: "2023-11-18", start_time: "14:00", end_time: "16:00", reason: "Lab Work", details: "Need to complete programming assignment", status: "rejected", created_at: "2023-11-10 08:30:00" },
                { id: 1008, user_id: 203, user_name: "Dr. Lisa Park", user_type: "faculty", user_dept: "Physics", room_id: 102, room_number: "302", date: "2023-11-17", start_time: "13:00", end_time: "15:00", reason: "Physics Lab", details: "Advanced physics lab for seniors", status: "approved", created_at: "2023-11-05 15:40:00" }
            ],
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
            ],
            conflicts: [
                { id: "CF001", room_id: 101, room_number: "301", date: "2023-11-15", time: "10:30-11:30", bookings: [
                    { id: 1001, user_name: "Dr. Sarah Johnson", user_type: "faculty", time: "09:00-11:00", priority: "high" },
                    { id: 1004, user_name: "Prof. Michael Chen", user_type: "faculty", time: "11:00-13:00", priority: "high" }
                ], type: "Time Overlap", auto_resolved: false },
                { id: "CF002", room_id: 108, room_number: "Lab-3", date: "2023-11-18", time: "15:00-17:00", bookings: [
                    { id: 1003, user_name: "Robotics Club", user_type: "club", time: "16:00-18:00", priority: "medium" },
                    { id: 1007, user_name: "Alex Rivera", user_type: "student", time: "14:00-16:00", priority: "low" }
                ], type: "Priority Conflict", auto_resolved: true },
                { id: "CF003", room_id: 105, room_number: "205", date: "2023-11-16", time: "14:00-16:00", bookings: [
                    { id: 1002, user_name: "John Smith", user_type: "student", time: "14:00-16:00", priority: "low" },
                    { id: 1010, user_name: "Physics Club", user_type: "club", time: "14:00-16:00", priority: "medium" }
                ], type: "Double Booking", auto_resolved: false }
            ],
            users: [
                { id: 1, name: "System Administrator", email: "admin@university.edu", type: "admin", dept: "Administration", status: "active" },
                { id: 201, name: "Dr. Sarah Johnson", email: "s.johnson@university.edu", type: "faculty", dept: "Computer Science", status: "active" },
                { id: 202, name: "Prof. Michael Chen", email: "m.chen@university.edu", type: "faculty", dept: "Mathematics", status: "active" },
                { id: 203, name: "Dr. Lisa Park", email: "l.park@university.edu", type: "faculty", dept: "Physics", status: "active" },
                { id: 101, name: "John Smith", email: "john.smith@student.university.edu", type: "student", dept: "Engineering", status: "active" },
                { id: 102, name: "Emma Wilson", email: "emma.wilson@student.university.edu", type: "student", dept: "Business", status: "active" },
                { id: 103, name: "Alex Rivera", email: "alex.rivera@student.university.edu", type: "student", dept: "Computer Science", status: "active" },
                { id: 301, name: "Robotics Club", email: "robotics@club.university.edu", type: "club", dept: "Robotics Club", status: "active" },
                { id: 302, name: "Drama Club", email: "drama@club.university.edu", type: "club", dept: "Drama Club", status: "active" },
                { id: 303, name: "Debate Society", email: "debate@club.university.edu", type: "club", dept: "Debate Society", status: "inactive" }
            ]
        };

        // Initialize date picker
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date picker for filter
            flatpickr("#filter-date", {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "F j, Y"
            });
            
            // Set up navigation
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    navItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    const targetId = this.getAttribute('data-target');
                    showSection(targetId);
                });
            });
            
            // Populate demo data
            populateDashboard();
            populateBookingsTable();
            populateRoomsTable();
            populateConflictsList();
            populateUsersList();
            
            // Set up form submissions
            document.getElementById('addRoomForm').addEventListener('submit', function(e) {
                e.preventDefault();
                addNewRoom();
            });
            
            document.getElementById('addUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                addNewUser();
            });
            
            document.getElementById('addBookingForm').addEventListener('submit', function(e) {
                e.preventDefault();
                addNewBooking();
            });
            
            // Logout button
            document.getElementById('logout-btn').addEventListener('click', function() {
                if (confirm("Are you sure you want to logout?")) {
                    alert("Logged out successfully. this redirect to login page.");
                }
            });
        });

        // Show section function
        function showSection(sectionId) {
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update page title
            const pageTitle = document.getElementById('page-title');
            const navItem = document.querySelector(`.nav-item[data-target="${sectionId}"]`);
            const sectionName = navItem ? navItem.querySelector('span').textContent : 'Dashboard';
            pageTitle.textContent = sectionName;
            
            // Update URL hash for bookmarking
            window.location.hash = sectionId;
        }

        // Populate dashboard
        function populateDashboard() {
            // Update counts
            const pendingCount = demoData.bookings.filter(b => b.status === 'pending').length;
            document.getElementById('pending-bookings-count').textContent = pendingCount;
            document.getElementById('total-rooms-count').textContent = demoData.rooms.length;
            document.getElementById('active-conflicts-count').textContent = demoData.conflicts.filter(c => !c.auto_resolved).length;
            document.getElementById('total-users-count').textContent = demoData.users.length;
            
            // Populate recent bookings table
            const container = document.getElementById('recent-bookings');
            let html = '';
            
            // Get first 5 bookings sorted by creation date (newest first)
            const recentBookings = [...demoData.bookings]
                .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
                .slice(0, 5);
            
            recentBookings.forEach(booking => {
                const statusClass = `status-${booking.status}`;
                const statusText = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);
                const badgeClass = `badge-${booking.user_type}`;
                
                html += `
                <tr>
                    <td>
                        <strong>BK${booking.id}</strong><br>
                        <small>${booking.reason}</small>
                    </td>
                    <td>
                        <div>${booking.user_name}</div>
                        <span class="badge ${badgeClass}">${booking.user_type}</span>
                    </td>
                    <td>Room ${booking.room_number}</td>
                    <td>${booking.date}<br>${booking.start_time} - ${booking.end_time}</td>
                    <td><span class="status ${statusClass}">${statusText}</span></td>
                    <td>
                        <div class="action-buttons">
                            ${booking.status === 'pending' ? 
                            `<button class="action-btn btn-success" onclick="approveBooking(${booking.id})">Approve</button>
                            <button class="action-btn btn-danger" onclick="rejectBooking(${booking.id})">Reject</button>` : ''}
                            <button class="action-btn btn-outline" onclick="viewBooking(${booking.id})">View</button>
                        </div>
                    </td>
                </tr>
                `;
            });
            
            container.innerHTML = html;
        }

        // Populate bookings table with filtering
        function populateBookingsTable(filteredBookings = null) {
            const container = document.getElementById('bookings-table');
            let html = '';
            
            const bookingsToShow = filteredBookings || demoData.bookings;
            
            bookingsToShow.forEach(booking => {
                const statusClass = `status-${booking.status}`;
                const statusText = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);
                const badgeClass = `badge-${booking.user_type}`;
                
                html += `
                <tr>
                    <td><strong>BK${booking.id}</strong></td>
                    <td>
                        <div>${booking.user_name}</div>
                        <div style="font-size: 0.8rem; color: var(--gray);">${booking.user_dept}</div>
                    </td>
                    <td><span class="badge ${badgeClass}">${booking.user_type}</span></td>
                    <td>Room ${booking.room_number}</td>
                    <td>${booking.date}<br>${booking.start_time} - ${booking.end_time}</td>
                    <td>${booking.reason}</td>
                    <td><span class="status ${statusClass}">${statusText}</span></td>
                    <td>
                        <div class="action-buttons">
                            ${booking.status === 'pending' ? 
                            `<button class="action-btn btn-success btn-sm" onclick="approveBooking(${booking.id})">Approve</button>
                            <button class="action-btn btn-danger btn-sm" onclick="rejectBooking(${booking.id})">Reject</button>` : ''}
                            <button class="action-btn btn-warning btn-sm" onclick="cancelBooking(${booking.id})">Cancel</button>
                            <button class="action-btn btn-outline btn-sm" onclick="viewBooking(${booking.id})">View</button>
                        </div>
                    </td>
                </tr>
                `;
            });
            
            container.innerHTML = html;
        }

        // Filter bookings based on selected filters
        function filterBookings() {
            const statusFilter = document.getElementById('filter-status').value;
            const userTypeFilter = document.getElementById('filter-user-type').value;
            const dateFilter = document.getElementById('filter-date').value;
            
            let filteredBookings = demoData.bookings;
            
            // Apply status filter
            if (statusFilter !== 'all') {
                filteredBookings = filteredBookings.filter(booking => booking.status === statusFilter);
            }
            
            // Apply user type filter
            if (userTypeFilter !== 'all') {
                filteredBookings = filteredBookings.filter(booking => booking.user_type === userTypeFilter);
            }
            
            // Apply date filter (if date range is selected)
            if (dateFilter) {
                const dates = dateFilter.split(' to ');
                const startDate = dates[0] ? new Date(dates[0]) : null;
                const endDate = dates[1] ? new Date(dates[1]) : null;
                
                filteredBookings = filteredBookings.filter(booking => {
                    const bookingDate = new Date(booking.date);
                    
                    if (startDate && endDate) {
                        return bookingDate >= startDate && bookingDate <= endDate;
                    } else if (startDate) {
                        return bookingDate >= startDate;
                    }
                    return true;
                });
            }
            
            populateBookingsTable(filteredBookings);
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('filter-status').value = 'all';
            document.getElementById('filter-user-type').value = 'all';
            document.getElementById('filter-date').value = '';
            populateBookingsTable();
        }

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

        // Populate conflicts list
        function populateConflictsList() {
            const container = document.getElementById('conflicts-list');
            let html = '';
            
            demoData.conflicts.forEach(conflict => {
                const autoResolvedBadge = conflict.auto_resolved ? 
                    `<span class="badge" style="background-color: #d4edda; color: #155724;">Auto-Resolved</span>` : 
                    `<span class="badge" style="background-color: #f8d7da; color: #721c24;">Needs Attention</span>`;
                
                let bookingsHtml = '';
                conflict.bookings.forEach((booking, index) => {
                    const priorityClass = `priority-${booking.priority}`;
                    bookingsHtml += `
                    <div class="conflict-booking">
                        <div>
                            <strong>${booking.user_name}</strong>
                            <div style="font-size: 0.9rem; color: var(--gray);">Booking ID: BK${booking.id}</div>
                            <div style="font-size: 0.9rem;">Time: ${booking.time}</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="priority-indicator">
                                <div class="priority-dot ${priorityClass}"></div>
                                <span>${booking.priority} priority</span>
                            </div>
                            <div>
                                <span class="badge badge-${booking.user_type}">${booking.user_type}</span>
                            </div>
                        </div>
                    </div>
                    `;
                });
                
                html += `
                <div class="conflict-item">
                    <div class="conflict-header">
                        <div>
                            <strong>${conflict.type}: Room ${conflict.room_number}</strong>
                            <div style="color: var(--gray); font-size: 0.9rem; margin-top: 5px;">
                                Date: ${conflict.date} | Time: ${conflict.time} | ${autoResolvedBadge}
                            </div>
                        </div>
                        <div>
                            ${!conflict.auto_resolved ? 
                            `<button class="btn btn-primary" onclick="resolveConflict('${conflict.id}')">Resolve Manually</button>` : 
                            `<button class="btn btn-outline" onclick="viewConflict('${conflict.id}')">View Details</button>`}
                        </div>
                    </div>
                    <div>
                        <p style="margin-bottom: 10px; font-weight: 600;">Conflicting Bookings:</p>
                        ${bookingsHtml}
                    </div>
                </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Populate users list
        function populateUsersList() {
            const container = document.getElementById('users-list');
            let html = '';
            
            demoData.users.forEach(user => {
                const badgeClass = `badge-${user.type}`;
                const statusBadge = user.status === 'active' ? 
                    `<span class="badge" style="background-color: #d4edda; color: #155724;">Active</span>` : 
                    `<span class="badge" style="background-color: #f8d7da; color: #721c24;">Inactive</span>`;
                
                html += `
                <div class="user-list-item">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div class="user-avatar-small">${user.name.charAt(0)}</div>
                        <div>
                            <strong>${user.name}</strong>
                            <div style="font-size: 0.9rem; color: var(--gray);">${user.email}</div>
                            <div style="display: flex; gap: 10px; margin-top: 5px;">
                                <span class="badge ${badgeClass}">${user.type}</span>
                                ${statusBadge}
                            </div>
                        </div>
                    </div>
                    <div>
                        <button class="action-btn btn-outline" onclick="editUser(${user.id})">Edit</button>
                        <button class="action-btn btn-danger" onclick="deleteUser(${user.id})">Delete</button>
                    </div>
                </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Booking management functions
        function approveBooking(bookingId) {
            if (confirm(`Approve booking BK${bookingId}?`)) {
                alert(`Booking BK${bookingId} approved!`);
                // this update the backend
                populateDashboard();
                populateBookingsTable();
            }
        }
        
        function rejectBooking(bookingId) {
            if (confirm(`Reject booking BK${bookingId}?`)) {
                alert(`Booking BK${bookingId} rejected!`);
                // this update the backend
                populateDashboard();
                populateBookingsTable();
            }
        }
        
        function cancelBooking(bookingId) {
            if (confirm(`Cancel booking BK${bookingId}? This action cannot be undone.`)) {
                alert(`Booking BK${bookingId} cancelled!`);
                //this update the backend
                populateDashboard();
                populateBookingsTable();
            }
        }
        
        function viewBooking(bookingId) {
            alert(`Viewing details for booking BK${bookingId}`);
            // this open a modal with booking details
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

        // Conflict management functions
        function resolveConflict(conflictId) {
            alert(`Resolving conflict ${conflictId}`);
            // this open a resolution modal
        }
        
        function viewConflict(conflictId) {
            alert(`Viewing details for conflict ${conflictId}`);
        }
        
        function runConflictDetection() {
            alert("Running conflict detection... Found 2 new conflicts!");
            // this call the backend
            populateConflictsList();
        }
        
        function autoResolveConflicts() {
            if (confirm("Auto-resolve all conflicts based on priority rules?")) {
                alert("Auto-resolving conflicts... 3 conflicts resolved!");
                // this call the backend
                populateConflictsList();
                populateDashboard();
            }
        }

        // User management functions
        function editUser(userId) {
            alert(`Editing user with ID ${userId}`);
            // this open the edit user modal
        }
        
        function deleteUser(userId) {
            if (confirm("Are you sure you want to delete this user? This action cannot be undone.")) {
                alert(`User ${userId} deleted`);
                // this update the backend
                populateUsersList();
                populateDashboard();
            }
        }

        // Modal functions
        function showAddRoomModal() {
            document.getElementById('addRoomModal').style.display = 'flex';
        }
        
        function showAddUserModal() {
            // Populate user type options in the modal
            const userTypeSelect = document.getElementById('userType');
            userTypeSelect.innerHTML = `
                <option value="">Select User Type</option>
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
                <option value="club">Club Representative</option>
                <option value="admin">Administrator</option>
            `;
            
            document.getElementById('addUserModal').style.display = 'flex';
        }
        
        function showAddBookingModal() {
            // Populate user dropdown
            const userSelect = document.getElementById('bookingUser');
            let userOptions = '<option value="">Select User</option>';
            demoData.users.forEach(user => {
                userOptions += `<option value="${user.id}">${user.name} (${user.type})</option>`;
            });
            userSelect.innerHTML = userOptions;
            
            // Populate room dropdown
            const roomSelect = document.getElementById('bookingRoom');
            let roomOptions = '<option value="">Select Room</option>';
            demoData.rooms.forEach(room => {
                if (room.status === 'available' && !room.maintenance) {
                    roomOptions += `<option value="${room.id}">${room.room_number} (${room.type}, ${room.capacity} seats)</option>`;
                }
            });
            roomSelect.innerHTML = roomOptions;
            
            // Set default date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('bookingDate').value = tomorrow.toISOString().split('T')[0];
            
            document.getElementById('addBookingModal').style.display = 'flex';
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
            populateDashboard();
        }
        
        function addNewUser() {
            const userName = document.getElementById('userName').value;
            const userEmail = document.getElementById('userEmail').value;
            const userType = document.getElementById('userType').value;
            const userDept = document.getElementById('userDept').value;
            const userPassword = document.getElementById('userPassword').value;
            const userConfirmPassword = document.getElementById('userConfirmPassword').value;
            
            if (userPassword !== userConfirmPassword) {
                alert("Passwords do not match!");
                return;
            }
            
            alert(`New user added:\nName: ${userName}\nEmail: ${userEmail}\nType: ${userType}\nDepartment: ${userDept}`);
            
            // this send data to backend
            closeModal('addUserModal');
            populateUsersList();
            populateDashboard();
        }
        
        function addNewBooking() {
            const bookingUser = document.getElementById('bookingUser').value;
            const bookingRoom = document.getElementById('bookingRoom').value;
            const bookingDate = document.getElementById('bookingDate').value;
            const bookingStartTime = document.getElementById('bookingStartTime').value;
            const bookingEndTime = document.getElementById('bookingEndTime').value;
            const bookingReason = document.getElementById('bookingReason').value;
            const bookingDetails = document.getElementById('bookingDetails').value;
            const bookingStatus = document.getElementById('bookingStatus').value;
            
            // Find user and room details
            const user = demoData.users.find(u => u.id == bookingUser);
            const room = demoData.rooms.find(r => r.id == bookingRoom);
            
            if (!user || !room) {
                alert("Please select a valid user and room");
                return;
            }
            
            alert(`New booking created:\nUser: ${user.name}\nRoom: ${room.room_number}\nDate: ${bookingDate}\nTime: ${bookingStartTime} - ${bookingEndTime}\nReason: ${bookingReason}\nStatus: ${bookingStatus}`);
            
            // this send data to backend
            closeModal('addBookingModal');
            populateBookingsTable();
            populateDashboard();
        }

        // Save system settings
        function saveSettings() {
            const maxBookingHours = document.getElementById('max-booking-hours').value;
            const advanceBookingDays = document.getElementById('advance-booking-days').value;
            const autoCancelHours = document.getElementById('auto-cancel-hours').value;
            const autoApproveFaculty = document.getElementById('auto-approve-faculty').checked;
            const conflictAlerts = document.getElementById('conflict-alerts').checked;
            const overtimeRequests = document.getElementById('overtime-requests').checked;
            
            alert(`Settings saved!\n\nMax Booking Hours: ${maxBookingHours}\nAdvance Booking Days: ${advanceBookingDays}\nAuto-cancel After: ${autoCancelHours} hours\nAuto-approve Faculty: ${autoApproveFaculty ? 'Yes' : 'No'}\nConflict Alerts: ${conflictAlerts ? 'Yes' : 'No'}\nOvertime Requests: ${overtimeRequests ? 'Allowed' : 'Not Allowed'}`);
            
            // this send data to backend
        }
    </script>
</body>
</html>