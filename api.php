<?php
// api.php - Gestore richieste per prenotazioni barbiere (JSON backend)

// --- Configurazione ---
$usersFile = __DIR__ . '/users.json';
$bookingsFile = __DIR__ . '/bookings.json';
$adminPassword = 'passwordsegreta'; // Cambiala!

// --- Setup Iniziale ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // Non mostrare errori PHP nel browser
ini_set('log_errors', 1);
// Assicurati che il percorso del log sia scrivibile dal server web!
// ini_set('error_log', __DIR__ . '/php_error.log'); // Esempio: abilita se necessario
date_default_timezone_set('Europe/Rome');

// Imposta l'header JSON all'inizio
header('Content-Type: application/json');
// CORS Headers (per test locale)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Gestione richiesta OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

// --- Funzioni Helper ---
function readJsonFile($filename) {
    if (!file_exists($filename)) return [];
    $json = @file_get_contents($filename);
    if ($json === false) { error_log("Errore lettura file: " . $filename); return []; }
    if (empty($json)) return [];
    $data = @json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) return $data;
    error_log("Errore decode JSON o non è un array: " . $filename . " - Errore: " . json_last_error_msg());
    // @rename($filename, $filename . '.corrupted.' . time()); // Opzionale: rinomina file corrotto
    return [];
}
function writeJsonFile($filename, $data) {
    if (!is_array($data)) { error_log("Tentativo di scrivere dati non array in: " . $filename); return false; }
    $tempFilename = $filename . '.' . uniqid('', true) . '.tmp';
    // Rimuovi JSON_PRETTY_PRINT se causa problemi o non serve
    $json = json_encode($data, JSON_UNESCAPED_UNICODE); // Più compatto
    if ($json === false) { error_log("Errore encoding JSON per file: " . $filename . " - Errore: " . json_last_error_msg()); return false; }
    if (@file_put_contents($tempFilename, $json, LOCK_EX) === false) { error_log("Errore scrittura file temporaneo (LOCK_EX): " . $tempFilename . " per " . $filename); @unlink($tempFilename); return false; }
    if (!@rename($tempFilename, $filename)) { error_log("Errore rinomina file temporaneo a definitivo: " . $tempFilename . " -> " . $filename); @unlink($tempFilename); return false; }
    @chmod($filename, 0664);
    return true;
}
function clearOldBookingsIfNeeded($filename) {
    $bookings = readJsonFile($filename);
    if (empty($bookings)) return;
    $today = date('Y-m-d');
    $cleanedCount = 0;
    $currentBookings = [];
    foreach ($bookings as $booking) {
        if (isset($booking['date']) && is_string($booking['date']) && $booking['date'] >= $today) { $currentBookings[] = $booking; }
        else { $cleanedCount++; }
    }
    if ($cleanedCount > 0) {
        if (writeJsonFile($filename, $currentBookings)) { error_log("Pulite $cleanedCount prenotazioni vecchie."); }
        else { error_log("Errore scrittura prenotazioni pulite."); }
    }
}

// --- Gestione Richiesta ---
$response = ['success' => false, 'message' => 'Azione non specificata o non valida.']; // Default
$action = null;
$requestData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gestisce sia application/json che form-data
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $jsonInput = file_get_contents('php://input');
        $requestData = json_decode($jsonInput, true) ?? [];
    } else {
        $requestData = $_POST;
    }
    $action = $requestData['action'] ?? null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestData = $_GET;
    $action = $requestData['action'] ?? null;
}

// Flag per sapere se abbiamo già inviato una risposta
$responseSent = false;

try {
    switch ($action) {
        // --- Azioni Utente ---
        case 'signup':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido.');
            $username = trim($requestData['username'] ?? '');
            $password = $requestData['password'] ?? '';
            if (empty($username) || empty($password)) throw new Exception('Username e password richiesti.');
            $users = readJsonFile($usersFile);
            $userExists = false;
            foreach ($users as $user) { if (is_array($user) && isset($user['username']) && strtolower($user['username']) === strtolower($username)) { $userExists = true; break; } }
            if ($userExists) throw new Exception('Username già esistente.');
            $users[] = ['username' => $username, 'password' => $password]; // INSECURE! Hash in production
            if (!writeJsonFile($usersFile, $users)) throw new Exception('Errore salvataggio utente.');
            $response = ['success' => true, 'message' => 'Registrazione avvenuta!'];
            break;

        case 'login':
             if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido.');
            $username = trim($requestData['username'] ?? '');
            $password = $requestData['password'] ?? '';
            if (empty($username) || empty($password)) throw new Exception('Username e password richiesti.');
            $users = readJsonFile($usersFile);
            $loggedIn = false; $foundUser = null;
            foreach ($users as $user) { if (is_array($user) && isset($user['username'], $user['password']) && strtolower($user['username']) === strtolower($username) && $user['password'] === $password) { $loggedIn = true; $foundUser = $user['username']; break; } } // INSECURE! Verify hash in production
            if ($loggedIn) { $response = ['success' => true, 'username' => $foundUser]; }
            else { throw new Exception('Username o password non validi.'); }
            break;

        case 'getBookings':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new Exception('Metodo non valido.');
            $bookings = readJsonFile($bookingsFile);
            $validBookings = array_filter($bookings, fn($b) => is_array($b) && isset($b['date'], $b['time'], $b['user']));
            $response = ['success' => true, 'bookings' => array_values($validBookings)];
            break;

        case 'addBooking':
             if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido.');
             $date = $requestData['date'] ?? null; $time = $requestData['time'] ?? null; $user = $requestData['user'] ?? null; $comment = trim($requestData['comment'] ?? '');
             if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new Exception('Formato data non valido.');
             if (empty($time) || !preg_match('/^\d{2}:\d{2}$/', $time)) throw new Exception('Formato ora non valido.');
             if (empty($user)) throw new Exception('Utente non specificato.');
             $today = date('Y-m-d'); if ($date < $today) throw new Exception('Non prenotare nel passato.');
             $bookings = readJsonFile($bookingsFile); $conflict = false;
             foreach ($bookings as $booking) { if (is_array($booking) && isset($booking['date'], $booking['time']) && $booking['date'] === $date && $booking['time'] === $time) { $conflict = true; break; } }
             if ($conflict) throw new Exception('Fascia oraria già prenotata.');
             $bookings[] = [ 'date' => $date, 'time' => $time, 'user' => $user, 'comment' => htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') ];
             if (!writeJsonFile($bookingsFile, $bookings)) throw new Exception('Errore salvataggio prenotazione.');
             $response = ['success' => true, 'message' => 'Prenotazione aggiunta!'];
             break;

        // --- Azioni Admin ---
        case 'adminLogin':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido.');
            $password = $requestData['password'] ?? '';
            if ($password === $adminPassword) { $response = ['success' => true]; }
            else { throw new Exception('Password amministratore errata.'); }
            break;

        case 'getAdminBookings':
             if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new Exception('Metodo non valido.');
             clearOldBookingsIfNeeded($bookingsFile); // Pulisce prima
             $currentBookings = readJsonFile($bookingsFile);
             $validBookings = array_filter($currentBookings, fn($b) => is_array($b) && isset($b['date'], $b['time'], $b['user']));
             $response = ['success' => true, 'bookings' => array_values($validBookings)];
             break;

        default:
            // L'azione non è valida o non è stata fornita
             http_response_code(400); // Bad Request
             // $response rimane quella di default
            break;
    }
} catch (Exception $e) {
    // Gestisce eccezioni lanciate esplicitamente nel codice sopra
    $response = ['success' => false, 'message' => $e->getMessage()];
    http_response_code(400); // Errore client o logica
    error_log("Errore API catturato in try-catch: " . $e->getMessage());
} catch (Throwable $t) {
     // Gestisce errori PHP gravi (es. Error, TypeError) PHP 7+
     $response = ['success' => false, 'message' => 'Errore interno del server.'];
     http_response_code(500); // Internal Server Error
     error_log("Errore PHP grave catturato: " . $t->getMessage() . " in " . $t->getFile() . ":" . $t->getLine());
} finally {
    // Assicura che venga sempre inviata una risposta JSON valida
    if (!$responseSent) { // Controlla se è già stata inviata (non dovrebbe in questo flusso)
        // Se il codice HTTP non è stato impostato specificamente, usa 200 per successo, 400 per fallimento di default
        if (!headers_sent() && http_response_code() === 200 && !$response['success']) {
             http_response_code(400); // Imposta 400 se success è false ma il codice è ancora 200
        }
        echo json_encode($response);
        $responseSent = true; // Segna come inviata
    }
}

exit; // Termina lo script

?>
