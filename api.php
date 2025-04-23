<?php
// api.php - Gestore richieste per prenotazioni barbiere (JSON backend)

// --- Configurazione ---
$usersFile = __DIR__ . '/users.json';
$bookingsFile = __DIR__ . '/bookings.json';
$adminPassword = 'passwordsegreta'; // Cambiala!

// --- Setup Iniziale ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/php_error.log'); // Abilita se necessario e verifica percorso
date_default_timezone_set('Europe/Rome');

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

// --- Funzioni Helper ---
function readJsonFile($filename) {
    // ... (implementazione precedente) ...
    if (!file_exists($filename)) return [];
    $json = @file_get_contents($filename);
    if ($json === false) { error_log("Errore lettura file: " . $filename); return []; }
    if (empty($json)) return [];
    $data = @json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) return $data;
    error_log("Errore decode JSON o non è un array: " . $filename . " - Errore: " . json_last_error_msg());
    return [];
}
function writeJsonFile($filename, $data) {
    // ... (implementazione precedente con file temporaneo) ...
     if (!is_array($data)) { error_log("Tentativo di scrivere dati non array in: " . $filename); return false; }
    $tempFilename = $filename . '.' . uniqid('', true) . '.tmp';
    $json = json_encode($data, JSON_UNESCAPED_UNICODE); // Rimosso PRETTY_PRINT
    if ($json === false) { error_log("Errore encoding JSON per file: " . $filename . " - Errore: " . json_last_error_msg()); return false; }
    if (@file_put_contents($tempFilename, $json, LOCK_EX) === false) { error_log("Errore scrittura file temporaneo (LOCK_EX): " . $tempFilename . " per " . $filename); @unlink($tempFilename); return false; }
    if (!@rename($tempFilename, $filename)) { error_log("Errore rinomina file temporaneo a definitivo: " . $tempFilename . " -> " . $filename); @unlink($tempFilename); return false; }
    @chmod($filename, 0664);
    return true;
}
function clearOldBookingsIfNeeded($filename) {
    // ... (implementazione precedente) ...
    $bookings = readJsonFile($filename);
    if (empty($bookings)) return;
    $today = date('Y-m-d'); $cleanedCount = 0; $currentBookings = [];
    foreach ($bookings as $booking) { if (isset($booking['date']) && is_string($booking['date']) && $booking['date'] >= $today) { $currentBookings[] = $booking; } else { $cleanedCount++; } }
    if ($cleanedCount > 0) { if (writeJsonFile($filename, $currentBookings)) { error_log("Pulite $cleanedCount prenotazioni vecchie."); } else { error_log("Errore scrittura prenotazioni pulite."); } }
}

// --- Gestione Richiesta ---
$response = ['success' => false, 'message' => 'Azione non specificata o non valida.'];
$action = null;
$requestData = [];

// Determina metodo e leggi dati
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $jsonInput = file_get_contents('php://input');
        $requestData = json_decode($jsonInput, true) ?? [];
    } else {
        $requestData = $_POST; // Assume form-data se non JSON
    }
    $action = $requestData['action'] ?? null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestData = $_GET;
    $action = $requestData['action'] ?? null;
}

$responseSent = false;
// Log dell'azione richiesta
error_log("API richiesta ricevuta: Metodo=" . $_SERVER['REQUEST_METHOD'] . ", Azione=" . ($action ?? 'Nessuna'));

try {
    switch ($action) {
        case 'signup':
            error_log("API Azione: signup"); // Debug
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido.');
            $username = trim($requestData['username'] ?? '');
            $password = $requestData['password'] ?? '';
            error_log("Signup: Ricevuto username='{$username}', password_length=" . strlen($password)); // Debug
            if (empty($username) || empty($password)) throw new Exception('Username e password richiesti.');

            $users = readJsonFile($usersFile);
            error_log("Signup: Letti " . count($users) . " utenti esistenti."); // Debug
            $userExists = false;
            foreach ($users as $user) { if (is_array($user) && isset($user['username']) && strtolower($user['username']) === strtolower($username)) { $userExists = true; break; } }

            if ($userExists) {
                 error_log("Signup: Username '{$username}' già esistente."); // Debug
                 throw new Exception('Username già esistente.');
            }

            $users[] = ['username' => $username, 'password' => $password]; // INSECURE!
            error_log("Signup: Tentativo di scrittura nuovo utente '{$username}'."); // Debug
            if (!writeJsonFile($usersFile, $users)) {
                 error_log("Signup: Fallimento scrittura file utenti."); // Debug
                 throw new Exception('Errore salvataggio utente. Controlla i permessi del server.');
            }
            error_log("Signup: Utente '{$username}' scritto con successo."); // Debug
            $response = ['success' => true, 'message' => 'Registrazione avvenuta!'];
            break;

        // ... (altri case: login, getBookings, addBooking, adminLogin, getAdminBookings - invariati rispetto a prima) ...
        case 'login':
             error_log("API Azione: login");
             if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido.');
            $username = trim($requestData['username'] ?? '');
            $password = $requestData['password'] ?? '';
            if (empty($username) || empty($password)) throw new Exception('Username e password richiesti.');
            $users = readJsonFile($usersFile); $loggedIn = false; $foundUser = null;
            foreach ($users as $user) { if (is_array($user) && isset($user['username'], $user['password']) && strtolower($user['username']) === strtolower($username) && $user['password'] === $password) { $loggedIn = true; $foundUser = $user['username']; break; } }
            if ($loggedIn) { $response = ['success' => true, 'username' => $foundUser]; }
            else { throw new Exception('Username o password non validi.'); }
            break;
        case 'getBookings':
            error_log("API Azione: getBookings");
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new Exception('Metodo non valido.');
            $bookings = readJsonFile($bookingsFile);
            $validBookings = array_filter($bookings, fn($b) => is_array($b) && isset($b['date'], $b['time'], $b['user']));
            $response = ['success' => true, 'bookings' => array_values($validBookings)];
            break;
        case 'addBooking':
             error_log("API Azione: addBooking");
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
        case 'adminLogin':
            error_log("API Azione: adminLogin");
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido.');
            $password = $requestData['password'] ?? '';
            if ($password === $adminPassword) { $response = ['success' => true]; }
            else { throw new Exception('Password amministratore errata.'); }
            break;
        case 'getAdminBookings':
             error_log("API Azione: getAdminBookings");
             if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new Exception('Metodo non valido.');
             clearOldBookingsIfNeeded($bookingsFile);
             $currentBookings = readJsonFile($bookingsFile);
             $validBookings = array_filter($currentBookings, fn($b) => is_array($b) && isset($b['date'], $b['time'], $b['user']));
             $response = ['success' => true, 'bookings' => array_values($validBookings)];
             break;


        default:
             error_log("API Azione: Azione non valida o mancante: " . ($action ?? 'Nessuna')); // Log azione non valida
             http_response_code(400);
             // $response rimane quella di default
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
    http_response_code(400);
    error_log("Errore API (Exception): " . $e->getMessage());
} catch (Throwable $t) {
     $response = ['success' => false, 'message' => 'Errore interno del server.'];
     http_response_code(500);
     error_log("Errore PHP grave catturato: " . $t->getMessage() . " in " . $t->getFile() . ":" . $t->getLine());
} finally {
    // Assicura invio risposta JSON
    if (!$responseSent && !headers_sent()) {
        if (http_response_code() === 200 && !$response['success']) {
             http_response_code(400);
        }
        // Controlla se l'output è già iniziato (es. da un errore PHP non bufferizzato)
        if (ob_get_level() == 0 && !headers_sent()) {
             echo json_encode($response);
        } else {
             error_log("Impossibile inviare risposta JSON: output già iniziato o headers inviati.");
        }
        $responseSent = true;
    }
}

exit;

?>
