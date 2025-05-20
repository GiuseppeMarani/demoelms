<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'barber_shop';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Start session
session_start();

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isBarber() {
    global $conn;
    if (!isLoggedIn()) return false;
    
    $query = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    return $user && $user['role'] === 'barber';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireBarber() {
    if (!isLoggedIn() || !isBarber()) {
        header('Location: login.php');
        exit();
    }
}

// Helper functions
function formatTime($time) {
    return date('H:i', strtotime($time));
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function getStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'status-pending';
        case 'confirmed':
            return 'status-confirmed';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return '';
    }
}

function getStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'In attesa';
        case 'confirmed':
            return 'Confermato';
        case 'completed':
            return 'Completato';
        case 'cancelled':
            return 'Annullato';
        default:
            return $status;
    }
}

// Error handling
function displayError($message) {
    return '<div class="error-message"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($message) . '</div>';
}

// CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        header('HTTP/1.1 403 Forbidden');
        exit('Invalid CSRF token');
    }
}

// Input validation
function validatePhone($phone) {
    return preg_match('/^(\+39|0039)?\d{9,10}$/', $phone);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input));
}

// Time slot generation
function generateTimeSlots($date, $interval = 30) {
    $slots = [];
    $start = strtotime('09:00');
    $end = strtotime('18:00');
    
    for ($time = $start; $time <= $end; $time += ($interval * 60)) {
        // Skip lunch break (13:00-14:00)
        $current = date('H:i', $time);
        if ($current >= '13:00' && $current < '14:00') {
            continue;
        }
        $slots[] = $current;
    }
    
    return $slots;
}

// Check if time slot is available
function isTimeSlotAvailable($date, $time) {
    global $conn;
    
    $query = "SELECT COUNT(*) as count FROM reservations 
              WHERE reservation_date = ? AND reservation_time = ? 
              AND status NOT IN ('cancelled')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $date, $time);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'] == 0;
}

// Get user's active reservations
function getUserReservations($user_id, $date = null) {
    global $conn;
    
    $query = "SELECT r.*, s.name as service_name, s.duration, s.icon 
              FROM reservations r 
              JOIN services s ON r.service_id = s.id 
              WHERE r.user_id = ?";
    
    if ($date) {
        $query .= " AND DATE(r.reservation_date) = ?";
    }
    
    $query .= " ORDER BY r.reservation_date DESC, r.reservation_time DESC";
    
    $stmt = $conn->prepare($query);
    
    if ($date) {
        $stmt->bind_param("is", $user_id, $date);
    } else {
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get service icon HTML
function getServiceIcon($icon) {
    switch ($icon) {
        case 'cut':
            return '<i class="fas fa-cut"></i>';
        case 'beard':
            return '<i class="fas fa-user"></i>';
        case 'cut-beard':
            return '<i class="fas fa-user-tie"></i>';
        default:
            return '<i class="fas fa-cut"></i>';
    }
}
