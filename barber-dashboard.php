<?php
require_once 'config.php';

// Require barber login
requireBarber();

// Get date filter
$date_filter = $_GET['date'] ?? date('Y-m-d');

// Get appointments for the selected date
$query = "SELECT r.*, u.username, u.phone, s.name as service_name, s.duration, s.icon 
          FROM reservations r 
          JOIN users u ON r.user_id = u.id 
          JOIN services s ON r.service_id = s.id 
          WHERE DATE(r.reservation_date) = ? 
          ORDER BY r.reservation_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $date_filter);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Dashboard Barbiere</title>
    <link rel="icon" type="image/png" href="img/fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/css/datepicker.min.css" />
    <link rel="stylesheet" href="style.css" />
</head>
<body class="dashboard-page">
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen" style="display: none;">
        <img src="img/logo.gif" alt="Barbiere" class="loading-logo">
    </div>

    <div class="app">
        <!-- Header -->
        <header class="header">
            <img src="img/logo.gif" alt="Barbiere" class="header-logo">
            <div class="header-content" style="display: flex; align-items: center; gap: 12px;">
                <button onclick="changeDate(-1)" class="btn btn-secondary" style="padding: 8px 16px; font-size: 14px;">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="datePickerBtn" class="btn btn-secondary" style="padding: 8px 16px; font-size: 14px; min-width: 140px;">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo formatDate($date_filter); ?>
                </button>
                <button onclick="changeDate(1)" class="btn btn-secondary" style="padding: 8px 16px; font-size: 14px;">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main style="padding: 20px; padding-bottom: 80px;">
            <!-- Daily Summary -->
            <div class="card animate-slide-up">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin-bottom: 4px;">Appuntamenti oggi</h3>
                        <div style="color: var(--text-secondary);">
                            <?php echo count($appointments); ?> prenotazioni
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <h3 style="margin-bottom: 4px;">Totale</h3>
                        <div style="color: var(--accent);">
                            €<?php
                            $total = array_reduce($appointments, function($sum, $appointment) {
                                return $sum + ($appointment['status'] !== 'cancelled' ? 25 : 0);
                            }, 0);
                            echo number_format($total, 2);
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($appointments)): ?>
            <div class="card animate-slide-up" style="text-align: center; padding: 40px 20px;">
                <i class="fas fa-calendar-day" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 20px;"></i>
                <h2 style="margin-bottom: 10px;">Nessun appuntamento</h2>
                <p style="color: var(--text-secondary);">Non ci sono appuntamenti per questa data</p>
            </div>
            <?php else: ?>
            <div class="appointments-list">
                <?php foreach ($appointments as $index => $appointment): ?>
                <div class="card animate-slide-up appointment-card" 
                     data-id="<?php echo $appointment['id']; ?>"
                     style="display: flex; justify-content: space-between; margin-bottom: 16px; animation-delay: <?php echo $index * 0.1; ?>s;">
                    <!-- Left side: Info -->
                    <div style="flex: 1; padding-right: var(--space-4);">
                        <!-- Customer Info -->
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <div style="width: 48px; height: 48px; background: var(--surface-light); border-radius: 24px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="font-size: 20px;"></i>
                            </div>
                            <div>
                                <h3 style="margin-bottom: 4px;"><?php echo htmlspecialchars($appointment['username']); ?></h3>
                                <a href="tel:<?php echo htmlspecialchars($appointment['phone']); ?>" 
                                   style="color: var(--accent); text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-phone"></i>
                                    <?php echo htmlspecialchars($appointment['phone']); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Service Info -->
                        <div style="display: flex; gap: 16px; margin-bottom: 16px; background: var(--surface-light); padding: 12px; border-radius: 12px;">
                            <div style="width: 40px; height: 40px; background: var(--surface); border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                <?php echo getServiceIcon($appointment['icon']); ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($appointment['service_name']); ?></div>
                                <div style="color: var(--text-secondary); font-size: 14px;">
                                    <i class="fas fa-clock"></i> <?php echo $appointment['duration']; ?> min
                                </div>
                            </div>
                            <div style="font-weight: 600;">
                                €25.00
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <?php if ($appointment['status'] === 'pending'): ?>
                        <div class="status-buttons" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                            <button class="btn update-status" data-status="confirmed">
                                <i class="fas fa-check"></i> Conferma
                            </button>
                            <button class="btn btn-secondary update-status" data-status="cancelled" style="background: var(--error);">
                                <i class="fas fa-times"></i> Annulla
                            </button>
                        </div>
                        <?php elseif ($appointment['status'] === 'confirmed'): ?>
                        <button class="btn update-status" data-status="completed" style="width: 100%;">
                            <i class="fas fa-check-double"></i> Completa
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Right side: Time and Status -->
                    <div style="text-align: right; min-width: 120px; margin-left: var(--space-4);">
                        <div style="font-size: 24px; font-weight: 600; margin-bottom: 8px;">
                            <?php echo formatTime($appointment['reservation_time']); ?>
                        </div>
                        <div class="status-badge <?php echo getStatusClass($appointment['status']); ?>" style="justify-content: center; width: 100%;">
                            <?php echo getStatusText($appointment['status']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="index.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="barber-dashboard.php" class="nav-item active">
                <i class="fas fa-cut"></i>
                <span>Dashboard</span>
            </a>
            <a href="logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Esci</span>
            </a>
        </nav>
    </div>

    <div id="datePicker" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;"></div>

    <script src="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/js/datepicker-full.min.js"></script>
    <script>
    // Loading screen handler
    window.addEventListener('load', function() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (!sessionStorage.getItem('loadingShown')) {
            loadingScreen.style.display = 'flex';
            setTimeout(() => {
                loadingScreen.classList.add('fade-out');
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                }, 300);
            }, 1500);
            sessionStorage.setItem('loadingShown', 'true');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Date navigation function
        window.changeDate = function(offset) {
            const currentDate = new Date('<?php echo $date_filter; ?>');
            currentDate.setDate(currentDate.getDate() + offset);
            const newDate = currentDate.toISOString().split('T')[0];
            window.location.href = `barber-dashboard.php?date=${newDate}`;
        };

        // Initialize date picker
        const datePicker = new Datepicker(document.getElementById('datePicker'), {
            autohide: true,
            format: 'yyyy-mm-dd',
            language: 'it',
        });

        // Handle date picker button click
        document.getElementById('datePickerBtn').addEventListener('click', function() {
            document.getElementById('datePicker').style.display = 'block';
        });

        // Handle date selection
        datePicker.element.addEventListener('changeDate', function(e) {
            const date = e.detail.date;
            window.location.href = `barber-dashboard.php?date=${date.toISOString().split('T')[0]}`;
        });

        // Handle status updates
        document.querySelectorAll('.update-status').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.closest('.appointment-card').dataset.id;
                const newStatus = this.dataset.status;
                const button = this;

                button.innerHTML = '<div class="loading"></div>';
                button.disabled = true;

                fetch('update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${appointmentId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Errore durante l\'aggiornamento');
                        button.innerHTML = button.dataset.status === 'confirmed' ? 
                            '<i class="fas fa-check"></i> Conferma' : 
                            '<i class="fas fa-times"></i> Annulla';
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Errore durante l\'aggiornamento');
                    button.innerHTML = button.dataset.status === 'confirmed' ? 
                        '<i class="fas fa-check"></i> Conferma' : 
                        '<i class="fas fa-times"></i> Annulla';
                    button.disabled = false;
                });
            });
        });
    });
    </script>
</body>
</html>
