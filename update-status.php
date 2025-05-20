<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$reservation_id = $_POST['reservation_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$reservation_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Verify the reservation belongs to the current user
$user_id = $_SESSION['user_id'];
$check_query = "SELECT id FROM reservations WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Reservation not found or unauthorized']);
    exit;
}

// Update the reservation status
$update_query = "UPDATE reservations SET status = ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("sii", $status, $reservation_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update reservation']);
}
exit;
?>
