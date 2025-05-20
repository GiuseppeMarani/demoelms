<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: select-service.php');
    exit();
}

// Get and validate parameters
$service_id = isset($_GET['service']) ? (int)$_GET['service'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$time = isset($_GET['time']) ? $_GET['time'] : null;

if (!$service_id || !$date || !$time) {
    header('Location: select-service.php');
    exit();
}

// Validate service exists
$service_query = "SELECT id FROM services WHERE id = ?";
$stmt = $conn->prepare($service_query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service_result = $stmt->get_result();

if ($service_result->num_rows === 0) {
    header('Location: select-service.php');
    exit();
}

// Validate date format and range
try {
    $date_obj = new DateTime($date);
    $time_obj = new DateTime($time);
    $today = new DateTime();
    $max_date = (new DateTime())->modify('+2 months');

    if ($date_obj < $today || $date_obj > $max_date) {
        header('Location: select-service.php');
        exit();
    }
} catch (Exception $e) {
    header('Location: select-service.php');
    exit();
}

// Check if slot is available
$check_query = "SELECT id FROM reservations 
                WHERE reservation_date = ? 
                AND reservation_time = ? 
                AND status != 'cancelled'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ss", $date, $time);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header('Location: select-time.php?service=' . $service_id . '&date=' . $date);
    exit();
}

// Create reservation
$user_id = $_SESSION['user_id'];
$status = 'pending';

$insert_query = "INSERT INTO reservations 
                 (user_id, service_id, reservation_date, reservation_time, status) 
                 VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("iisss", $user_id, $service_id, $date, $time, $status);

if ($stmt->execute()) {
    header('Location: my-reservations.php');
    exit();
} else {
    header('Location: select-service.php?error=1');
    exit();
}
?>
