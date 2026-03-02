<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_POST['action'] ?? '' !== 'book_room') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'classorbit');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Sanitize inputs
$room_id = (int)$_POST['room_id'];
$datetime = $conn->real_escape_string($_POST['datetime']);
$reason = $conn->real_escape_string($_POST['reason']);
$urgent_needs = $conn->real_escape_string($_POST['urgent_needs'] ?? '');

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_dept = $_SESSION['user_dept'];
$priority_num = (int)($_SESSION['user_priority_num'] ?? 3);

// Check if already booked
$check = $conn->prepare("SELECT id FROM Checking WHERE Class_roomid = ? AND time = ?");
$check->bind_param('is', $room_id, $datetime);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This room is already booked at this time.']);
    exit();
}


$admin_id = 1; 
$available = 'no';

$conn->autocommit(false);

$insert_check = $conn->prepare("INSERT INTO Checking (time, available, Class_roomid, Adminid) VALUES (?, ?, ?, ?)");
$insert_check->bind_param('ssii', $datetime, $available, $room_id, $admin_id);
if (!$insert_check->execute()) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to create availability record']);
    exit();
}

$checking_id = $conn->insert_id;

$name = (int)$_SESSION['user_id']; 
$dpt = $user_dept;
$email = $user_email;

$insert_booking = $conn->prepare("INSERT INTO `ROOM Booking` (name, dpt, email, reason, Checkingid, prioritypriority_num, argent_need_thing) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insert_booking->bind_param('issssis', $name, $dpt, $email, $reason, $checking_id, $priority_num, $urgent_needs);

if ($insert_booking->execute()) {
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Room booked successfully!']);
} else {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Booking failed. Please try again.']);
}

$conn->close();
?>