<?php
// api.php - Gestore richieste per prenotazioni barbiere (JSON backend)

// --- Configurazione ---
$usersFile = __DIR__ . '/users.json'; // File utenti nella stessa cartella
$bookingsFile = __DIR__ . '/bookings.json'; // File prenotazioni nella stessa cartella
$adminPassword = 'passwordsegreta'; // Password admin (cambiala!)

// --- Setup Iniziale ---
error_reporting(E_ALL); // Mostra tutti gli errori (per debug)
ini_set('display_errors', 0); // Non mostrare errori all'utente finale
ini_set('log_errors', 1); // Logga errori su file (controlla la configurazione del tuo server PHP per il percorso del log)
date_default_timezone_set('Europe/Rome'); // Imposta il fuso orario corretto

// Imposta l'header per rispondere sempre in JSON
header('Content-Type: application/json');
// Permetti richieste da qualsiasi origine (per test locale, NON per produzione!)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Gestione richiesta OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204); // No Content
    exit(0);
}


// --- Funzioni Helper ---

/**
 * Legge dati da un file JSON.
 * Restituisce un array vuoto se il file non esiste o è corrotto.
 */
function readJsonFile($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    $json = @file_get_contents($filename);
    if ($json === false) {
        error_log("Errore lettura file: " . $filename);
        return [];
    }
    if (empty($json)) { // Gestisce file vuoto
        return [];
    }
    $data = @json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        return $data;
    } else {
         error_log("Errore decode JSON o non è un array: " . $filename . " - Errore: " . json_last_error_msg());
         // Potresti voler fare un backup del file corrotto qui
         // @rename($filename, $filename . '.corrupted.' . time());
         return []; // Ritorna vuoto se corrotto
    }
}

/**
 * Scrive dati in un file JSON in modo sicuro.
 * Restituisce true in caso di successo, false altrimenti.
 */
function writeJsonFile($filename, $data) {
    if (!is_array($data)) {
         error_log("Tentativo di scrivere dati non array in: " . $filename);
         return false;
    }
    // Usa un file temporaneo per la scrittura atomica (previene corruzione parziale)
    $tempFilename = $filename . '.' . uniqid('', true) . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("Errore encoding JSON per file: " . $filename . " - Errore: " . json_last_error_msg());
        return false;
    }
    // Scrivi sul file temporaneo
    if (@file_put_contents($tempFilename, $json, LOCK_EX) === false) {
         error_log("Errore scrittura file temporaneo (LOCK_EX): " . $tempFilename . " per " . $filename);
         @unlink($tempFilename); // Pulisci file temporaneo se esiste
         return false;
    }
    // Rinomina il file temporaneo a quello definitivo (operazione atomica sulla maggior parte dei sistemi)
    if (!@rename($tempFilename, $filename)) {
         error_log("Errore rinomina file temporaneo a definitivo: " . $tempFilename . " -> " . $filename);
         @unlink($tempFilename); // Pulisci file temporaneo
         return false;
    }
    // Imposta permessi corretti se possibile (es. 0664) - dipende dal server
    @chmod($filename, 0664);
    return true;
}

/**
 * Pulisce le prenotazioni passate.
 */
function clearOldBookingsIfNeeded($filename) {
    $bookings = readJsonFile($filename);
    if (empty($bookings)) return;

    $today = date('Y-m-d');
    $cleanedCount = 0;
    $currentBookings = [];

    foreach ($bookings as $booking) {
        if (isset($booking['date']) && is_string($booking['date']) && $booking['date'] >= $today) {
            $currentBookings[] = $booking;
        } else {
            $cleanedCount++;
        }
    }

    if ($cleanedCount > 0) {
        if (writeJsonFile($filename, $currentBookings)) {
            error_log("Pulite $cleanedCount prenotazioni vecchie.");
        } else {
             error_log("Errore durante la scrittura delle prenotazioni pulite.");
        }
    }
}

// --- Gestione Richiesta ---
$response = ['success' => false, 'message' => 'Azione non valida.']; // Risposta di default
$action = null;

// Leggi l'azione da POST o GET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
}

// Esegui l'azione richiesta
try {
    switch ($action) {
        // --- Azioni Utente ---
        case 'signup':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido per signup.');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? ''; // Non trimmare la password
            if (empty($username) || empty($password)) throw new Exception('Username e password richiesti.');

            $users = readJsonFile($usersFile);
            $userExists = false;
            foreach ($users as $user) {
                if (is_array($user) && isset($user['username']) && strtolower($user['username']) === strtolower($username)) { // Case-insensitive check
                    $userExists = true;
                    break;
                }
            }
            if ($userExists) throw new Exception('Username già esistente.');

            // !!! SICUREZZA: HASH PASSWORD IN PRODUZIONE !!!
            // $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // $users[] = ['username' => $username, 'password' => $hashedPassword];
            $users[] = ['username' => $username, 'password' => $password]; // INSECURE!

            if (!writeJsonFile($usersFile, $users)) throw new Exception('Errore salvataggio utente.');
            $response = ['success' => true, 'message' => 'Registrazione avvenuta con successo!'];
            break;

        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido per login.');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            if (empty($username) || empty($password)) throw new Exception('Username e password richiesti.');

            $users = readJsonFile($usersFile);
            $loggedIn = false;
            $foundUser = null;
            foreach ($users as $user) {
                if (is_array($user) && isset($user['username'], $user['password'])) {
                     // !!! SICUREZZA: USARE password_verify() IN PRODUZIONE !!!
                     // if (strtolower($user['username']) === strtolower($username) && password_verify($password, $user['password'])) {
                     if (strtolower($user['username']) === strtolower($username) && $user['password'] === $password) { // INSECURE!
                        $loggedIn = true;
                        $foundUser = $user['username']; // Restituisci username case-preserved
                        break;
                    }
                }
            }
            if ($loggedIn) {
                $response = ['success' => true, 'username' => $foundUser];
            } else {
                throw new Exception('Username o password non validi.');
            }
            break;

        case 'getBookings': // Usato dal cliente
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new Exception('Metodo non valido per getBookings.');
            $bookings = readJsonFile($bookingsFile);
            $validBookings = array_filter($bookings, fn($b) => is_array($b) && isset($b['date'], $b['time'], $b['user']));
            $response = ['success' => true, 'bookings' => array_values($validBookings)];
            break;

        case 'addBooking':
             if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido per addBooking.');
             $date = $_POST['date'] ?? null;
             $time = $_POST['time'] ?? null;
             $user = $_POST['user'] ?? null;
             $comment = trim($_POST['comment'] ?? '');

             if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new Exception('Formato data non valido.');
             if (empty($time) || !preg_match('/^\d{2}:\d{2}$/', $time)) throw new Exception('Formato ora non valido.');
             if (empty($user)) throw new Exception('Utente non specificato.');
             $today = date('Y-m-d');
             if ($date < $today) throw new Exception('Non è possibile prenotare per giorni passati.');

             $bookings = readJsonFile($bookingsFile);
             $conflict = false;
             foreach ($bookings as $booking) {
                 if (is_array($booking) && isset($booking['date'], $booking['time']) && $booking['date'] === $date && $booking['time'] === $time) {
                     $conflict = true; break;
                 }
             }
             if ($conflict) throw new Exception('Fascia oraria già prenotata.');

             $bookings[] = [
                 'date' => $date, 'time' => $time, 'user' => $user,
                 'comment' => htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') // Sanitizza commento
             ];
             if (!writeJsonFile($bookingsFile, $bookings)) throw new Exception('Errore salvataggio prenotazione.');
             $response = ['success' => true, 'message' => 'Prenotazione aggiunta con successo!'];
             break;

        // --- Azioni Admin ---
        case 'adminLogin':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Metodo non valido per adminLogin.');
            $password = $_POST['password'] ?? '';
            if ($password === $adminPassword) {
                $response = ['success' => true];
            } else {
                throw new Exception('Password amministratore errata.');
            }
            break;

        case 'getAdminBookings': // Usato dall'admin
             if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new Exception('Metodo non valido per getAdminBookings.');
             // Qui potresti aggiungere un controllo di autenticazione admin se necessario
             clearOldBookingsIfNeeded($bookingsFile); // Pulisce prima di inviare
             $currentBookings = readJsonFile($bookingsFile);
             $validBookings = array_filter($currentBookings, fn($b) => is_array($b) && isset($b['date'], $b['time'], $b['user']));
             $response = ['success' => true, 'bookings' => array_values($validBookings)];
             break;

        default:
            // $response rimane quella di default ('Azione non valida.')
            http_response_code(400); // Bad Request
            break;
    }
} catch (Exception $e) {
    // Gestisce eccezioni lanciate dalle azioni
    $response = ['success' => false, 'message' => $e->getMessage()];
    // Imposta codice HTTP appropriato (es. 400 per input errato, 500 per errori server)
    // Qui usiamo 400 per errori di logica/input, 500 verrebbe da errori PHP non catturati
    http_response_code(400);
     error_log("Errore API catturato: " . $e->getMessage()); // Logga l'errore
}

// Invia la risposta JSON finale
echo json_encode($response);
exit;

?>
