<?php
require_once 'config.php';
requireLogin();

$service_id = $_GET['service'] ?? null;
if (!$service_id) {
    header('Location: select-service.php');
    exit();
}

$query = "SELECT * FROM services WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    header('Location: select-service.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scegli Data - Barbiere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/css/datepicker.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="select-service.php" style="text-decoration: none; color: var(--text);">
            <i class="fas fa-arrow-left" style="font-size: 20px;"></i>
        </a>
        <h1 class="header-title">Prenota</h1>
    </header>

    <!-- Progress -->
    <div style="margin-bottom: var(--space-6);">
        <div style="color: var(--text-secondary); font-size: 14px; margin-bottom: var(--space-2);">
            Passo 2 di 4
        </div>
        <div style="height: 4px; background: #f0f0f0; border-radius: 2px;">
            <div style="width: 50%; height: 100%; background: var(--accent); border-radius: 2px;"></div>
        </div>
    </div>

    <!-- Selected Service -->
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
        </div>
    </div>

    <h2 class="header-subtitle">Scegli la data</h2>

    <!-- Calendar -->
    <div style="margin-bottom: var(--space-6);">
        <div id="calendar"></div>
    </div>

    <!-- Selected Date Display -->
    <div id="selectedDateDisplay" class="service-card" style="display: none; margin-bottom: var(--space-6);">
        <div class="service-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="service-info">
            <div style="color: var(--text-secondary); font-size: 13px; margin-bottom: 2px;">
                Data selezionata
            </div>
            <div id="selectedDateText" class="service-name"></div>
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
            Seleziona una data per il tuo appuntamento. Le domeniche non sono disponibili.
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

    <script src="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/js/datepicker-full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFormatter = {
                months: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
                days: ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato']
            };

            let selectedDate = null;
            const today = new Date();
            const maxDate = new Date();
            maxDate.setMonth(today.getMonth() + 2);

            const calendar = new Datepicker(document.getElementById('calendar'), {
                autohide: true,
                format: 'yyyy-mm-dd',
                minDate: today,
                maxDate: maxDate,
                weekStart: 1,
                daysOfWeekDisabled: [0],
                language: 'it',
                buttonClass: 'btn',
                todayHighlight: true,
                prevArrow: '<i class="fas fa-chevron-left"></i>',
                nextArrow: '<i class="fas fa-chevron-right"></i>'
            });

            calendar.element.addEventListener('changeDate', function(e) {
                selectedDate = e.detail.date;
                const continueBtn = document.getElementById('continueBtn');
                const selectedDateDisplay = document.getElementById('selectedDateDisplay');
                const selectedDateText = document.getElementById('selectedDateText');

                if (selectedDate) {
                    continueBtn.removeAttribute('disabled');
                    continueBtn.style.opacity = '1';
                    
                    const day = dateFormatter.days[selectedDate.getDay()];
                    const date = selectedDate.getDate();
                    const month = dateFormatter.months[selectedDate.getMonth()];
                    selectedDateText.textContent = `${day}, ${date} ${month}`;
                    selectedDateDisplay.style.display = 'flex';

                    continueBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    continueBtn.setAttribute('disabled', 'true');
                    continueBtn.style.opacity = '0.5';
                    selectedDateDisplay.style.display = 'none';
                }
            });

            document.getElementById('continueBtn').addEventListener('click', function() {
                if (selectedDate) {
                    const date = selectedDate.toISOString().split('T')[0];
                    window.location.href = `select-time.php?service=<?php echo $service_id; ?>&date=${date}`;
                }
            });
        });
    </script>
</body>
</html>
