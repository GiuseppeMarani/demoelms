<?php
require_once 'config.php';
requireLogin();

// Get and validate parameters
$service_id = $_GET['service'] ?? null;
$date = $_GET['date'] ?? null;
$time = $_GET['time'] ?? null;

if (!$service_id || !$date || !$time) {
    header('Location: select-service.php');
    exit();
}

// Validate date
$date_obj = DateTime::createFromFormat('Y-m-d', $date);
$today = new DateTime();
$max_date = (new DateTime())->modify('+2 months');

if (!$date_obj || $date_obj < $today || $date_obj > $max_date) {
    header('Location: select-service.php');
    exit();
}

// Get service details
$query = "SELECT * FROM services WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    header('Location: select-service.php');
    exit();
}

// Check if slot is still available
if (!isTimeSlotAvailable($date, $time)) {
    header('Location: select-time.php?service=' . $service_id . '&date=' . $date);
    exit();
}

// Calculate end time
$end_time = date('H:i', strtotime($time) + ($service['duration'] * 60));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Double check availability
    if (isTimeSlotAvailable($date, $time)) {
        $insert_query = "INSERT INTO reservations (user_id, service_id, reservation_date, reservation_time, status) 
                        VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiss", $_SESSION['user_id'], $service_id, $date, $time);
        
        if ($stmt->execute()) {
            header('Location: my-reservations.php');
            exit();
        }
    }
    header('Location: select-time.php?service=' . $service_id . '&date=' . $date);
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Conferma Prenotazione - Barbiere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="app">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <a href="select-time.php?service=<?php echo $service_id; ?>&date=<?php echo $date; ?>" style="text-decoration: none;">
                    <i class="fas fa-arrow-left" style="color: var(--text); font-size: 24px;"></i>
                </a>
                <div class="logo" style="font-size: 24px;">Prenota</div>
                <div style="width: 24px;"></div>
            </div>
        </header>

        <!-- Progress Bar -->
        <div style="background: var(--surface); padding: var(--spacing-lg);">
            <div style="max-width: 360px; margin: 0 auto;">
                <div style="color: var(--text-secondary); font-size: 16px; margin-bottom: var(--spacing-sm); font-weight: 500;">
                    Passo 4 di 4
                </div>
                <div style="height: 6px; background: var(--surface-light); border-radius: var(--radius-full);">
                    <div style="width: 100%; height: 100%; background: var(--accent); border-radius: var(--radius-full);"></div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main style="padding: var(--spacing-xl) var(--spacing-lg);">
            <h2 style="font-size: 28px; margin-bottom: var(--spacing-xl); letter-spacing: -0.5px;">
                Conferma prenotazione
            </h2>

            <!-- Appointment Summary -->
            <div class="card animate-slide-up">
                <!-- Service Info -->
                <div style="display: flex; align-items: center; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); padding-bottom: var(--spacing-xl); border-bottom: 1px solid var(--border);">
                    <div class="service-icon">
                        <?php echo getServiceIcon($service['icon']); ?>
                    </div>
                    <div class="service-info">
                        <div class="service-name">
                            <?php echo htmlspecialchars($service['name']); ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 16px;">
                            €<?php echo number_format($service['price'], 2); ?> • <?php echo $service['duration']; ?> min
                        </div>
                    </div>
                </div>

                <!-- Date and Time -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); padding-bottom: var(--spacing-xl); border-bottom: 1px solid var(--border);">
                    <div>
                        <div style="color: var(--text-secondary); font-size: 14px; margin-bottom: var(--spacing-xs); font-weight: 500;">
                            <i class="fas fa-calendar-alt"></i> Data
                        </div>
                        <div style="font-size: 18px; font-weight: 600;">
                            <?php echo formatDate($date); ?>
                        </div>
                    </div>
                    <div>
                        <div style="color: var(--text-secondary); font-size: 14px; margin-bottom: var(--spacing-xs); font-weight: 500;">
                            <i class="fas fa-clock"></i> Orario
                        </div>
                        <div style="font-size: 18px; font-weight: 600;">
                            <?php echo $time; ?> - <?php echo $end_time; ?>
                        </div>
                    </div>
                </div>

                <!-- Price Summary -->
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-md);">
                        <div style="color: var(--text-secondary);">Servizio</div>
                        <div>€<?php echo number_format($service['price'], 2); ?></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 600;">
                        <div>Totale</div>
                        <div>€<?php echo number_format($service['price'], 2); ?></div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card animate-slide-up" style="margin: var(--spacing-xl) 0; text-align: center;">
                <i class="fas fa-info-circle" style="font-size: 24px; color: var(--text-secondary); margin-bottom: var(--spacing-md);"></i>
                <p style="color: var(--text-secondary); font-size: 16px; line-height: 1.6;">
                    Verifica i dettagli della prenotazione prima di confermare.
                </p>
            </div>

            <!-- Confirm Button -->
            <form method="POST">
                <button type="submit" class="btn animate-slide-up" style="font-size: 18px;">
                    <i class="fas fa-check"></i> Conferma prenotazione
                </button>
            </form>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="index.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="my-reservations.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Prenotazioni</span>
            </a>
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user"></i>
                <span>Profilo</span>
            </a>
        </nav>
    </div>
</body>
</html>
