<?php
require_once 'config.php';
requireLogin();

$service_id = $_GET['service'] ?? null;
$date = $_GET['date'] ?? null;

if (!$service_id || !$date) {
    header('Location: select-service.php');
    exit();
}

// Validate date
$date_obj = DateTime::createFromFormat('Y-m-d', $date);
if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
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

// Get booked times for the selected date
$booked_query = "SELECT reservation_time FROM reservations WHERE reservation_date = ? AND status != 'cancelled'";
$stmt = $conn->prepare($booked_query);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
$booked_times = [];
while ($row = $result->fetch_assoc()) {
    $booked_times[] = $row['reservation_time'];
}

// Available time slots
$morning_slots = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00'];
$afternoon_slots = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scegli Orario - Barbiere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="select-date.php?service=<?php echo $service_id; ?>" style="text-decoration: none; color: var(--text);">
            <i class="fas fa-arrow-left" style="font-size: 20px;"></i>
        </a>
        <h1 class="header-title">Prenota</h1>
    </header>

    <!-- Progress -->
    <div style="margin-bottom: var(--space-6);">
        <div style="color: var(--text-secondary); font-size: 14px; margin-bottom: var(--space-2);">
            Passo 3 di 4
        </div>
        <div style="height: 4px; background: #f0f0f0; border-radius: 2px;">
            <div style="width: 75%; height: 100%; background: var(--accent); border-radius: 2px;"></div>
        </div>
    </div>

    <!-- Selected Service and Date -->
    <div class="service-card" style="margin-bottom: var(--space-6);">
        <div class="service-icon">
            <?php echo getServiceIcon($service['icon']); ?>
        </div>
        <div class="service-info">
            <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
            <div class="service-meta">
                <span>€<?php echo number_format($service['price'], 2); ?></span>
                <span>•</span>
                <span><?php echo $service['duration']; ?> min</span>
            </div>
            <div style="color: var(--text-secondary); font-size: 14px; margin-top: var(--space-2);">
                <?php 
                $date_obj = new DateTime($date);
                echo $date_obj->format('d/m/Y');
                ?>
            </div>
        </div>
    </div>

    <h2 class="header-subtitle">Scegli l'orario</h2>

    <!-- Morning Slots -->
    <div style="margin-bottom: var(--space-6);">
        <h3 style="font-size: 16px; color: var(--text-secondary); margin-bottom: var(--space-3);">Mattina</h3>
        <div class="time-slots">
            <?php foreach ($morning_slots as $time): 
                $is_booked = in_array($time, $booked_times);
                $class = $is_booked ? 'time-slot disabled' : 'time-slot';
            ?>
            <div class="<?php echo $class; ?>" <?php if (!$is_booked): ?>onclick="selectTime('<?php echo $time; ?>')"<?php endif; ?>>
                <?php echo $time; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Afternoon Slots -->
    <div style="margin-bottom: var(--space-6);">
        <h3 style="font-size: 16px; color: var(--text-secondary); margin-bottom: var(--space-3);">Pomeriggio</h3>
        <div class="time-slots">
            <?php foreach ($afternoon_slots as $time): 
                $is_booked = in_array($time, $booked_times);
                $class = $is_booked ? 'time-slot disabled' : 'time-slot';
            ?>
            <div class="<?php echo $class; ?>" <?php if (!$is_booked): ?>onclick="selectTime('<?php echo $time; ?>')"<?php endif; ?>>
                <?php echo $time; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Continue Button -->
    <button id="continueBtn" class="btn" disabled style="opacity: 0.5; margin-bottom: var(--space-6);">
        Continua
    </button>

    <!-- Info Card -->
    <div style="text-align: center; padding: var(--space-4); border: 1px solid var(--border); border-radius: var(--radius-md);">
        <i class="fas fa-info-circle" style="color: var(--text-secondary); font-size: 20px; margin-bottom: var(--space-3);"></i>
        <p style="color: var(--text-secondary); font-size: 14px;">
            Seleziona un orario disponibile per il tuo appuntamento.
        </p>
    </div>

    <!-- Navigation -->
    <nav class="nav">
        <a href="index.php" class="nav-link">
            <i class="fas fa-home"></i>
            Home
        </a>
        <a href="my-reservations.php" class="nav-link">
            <i class="fas fa-calendar-alt"></i>
            Prenotazioni
        </a>
        <a href="profile.php" class="nav-link">
            <i class="fas fa-user"></i>
            Profilo
        </a>
    </nav>

    <script>
    let selectedTimeSlot = null;

    function selectTime(time) {
        const timeSlots = document.querySelectorAll('.time-slot:not(.disabled)');
        timeSlots.forEach(slot => {
            slot.classList.remove('selected');
        });

        const selectedSlot = Array.from(timeSlots).find(slot => slot.textContent.trim() === time);
        if (selectedSlot) {
            selectedSlot.classList.add('selected');
            selectedTimeSlot = time;
            
            const continueBtn = document.getElementById('continueBtn');
            continueBtn.removeAttribute('disabled');
            continueBtn.style.opacity = '1';
            
            continueBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    document.getElementById('continueBtn').addEventListener('click', function() {
        if (selectedTimeSlot) {
            window.location.href = `book-appointment.php?service=<?php echo $service_id; ?>&date=<?php echo $date; ?>&time=${selectedTimeSlot}`;
        }
    });
    </script>
</body>
</html>
