<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$query = "SELECT r.*, s.name as service_name, s.icon 
          FROM reservations r 
          JOIN services s ON r.service_id = s.id 
          WHERE r.user_id = ? 
          ORDER BY r.reservation_date DESC, r.reservation_time DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I miei appuntamenti - Barbiere</title>
    <link rel="icon" type="image/png" href="img/fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .reservation-card {
            position: relative;
            margin-bottom: var(--space-4);
        }

        .cancel-button {
            position: absolute;
            top: var(--space-4);
            right: var(--space-4);
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--error);
            border-radius: var(--radius-sm);
            color: var(--error);
            background: var(--error-light);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .cancel-button:hover {
            background: var(--error);
            color: white;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.pending {
            background: #f3f4f6;
            color: #6b7280;
        }

        .status-badge.confirmed {
            background: #f0fdf4;
            color: #166534;
        }

        .status-badge.completed {
            background: #f8fafc;
            color: #0f172a;
        }

        .status-badge.cancelled {
            background: #fef2f2;
            color: #991b1b;
        }

        .empty-state {
            text-align: center;
            padding: var(--space-8) var(--space-4);
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: var(--space-4);
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen" style="display: none;">
        <img src="img/logo.gif" alt="Barbiere" class="loading-logo">
    </div>

    <div class="app">
        <!-- Simple Header -->
        <header class="header">
            <img src="img/logo.gif" alt="Barbiere" class="header-logo">
            <div class="header-content">
                <h1 style="font-size: 20px; margin: 0;">Le mie prenotazioni</h1>
            </div>
        </header>

    <?php if (empty($reservations)): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-alt"></i>
        <p>Non hai ancora prenotato nessun appuntamento</p>
        <a href="select-service.php" class="btn" style="margin-top: var(--space-6);">
            Prenota ora
        </a>
    </div>
    <?php else: ?>
    <div class="service-list" style="display: flex; flex-direction: column; gap: var(--space-4); padding: 20px;">
        <?php foreach ($reservations as $reservation): ?>
        <div class="service-card reservation-card" style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-4); margin: 0;">
            <!-- Left side: Service info -->
            <div style="display: flex; align-items: center; gap: var(--space-4); flex: 1;">
                <div class="service-icon" style="width: 60px; height: 60px; min-width: 60px;">
                    <?php echo getServiceIcon($reservation['icon']); ?>
                </div>
                <div class="service-info" style="flex: 1;">
                    <div class="service-name" style="font-size: 18px; margin-bottom: 4px;">
                        <?php echo htmlspecialchars($reservation['service_name']); ?>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <span class="status-badge <?php echo $reservation['status']; ?>">
                            <?php echo getStatusText($reservation['status']); ?>
                        </span>
                    </div>
                    <div style="color: var(--text-secondary); font-size: 14px;">
                        <i class="fas fa-clock"></i> <?php echo formatTime($reservation['reservation_time']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Right side: Date and cancel button -->
            <div style="text-align: right; margin-left: var(--space-4);">
                <div style="font-weight: 600; font-size: 18px; margin-bottom: 4px;">
                    <?php echo formatDate($reservation['reservation_date']); ?>
                </div>
                <?php if ($reservation['status'] === 'pending' || $reservation['status'] === 'confirmed'): ?>
                <button onclick="cancelReservation(<?php echo $reservation['id']; ?>)" 
                        class="cancel-button" style="position: static; margin-top: 8px;">
                    <i class="fas fa-times"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    </div>


    <!-- Refined Back Button -->
    <div style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); width: calc(100% - 40px); max-width: 400px; z-index: 1000;">
        <a href="javascript:history.back()" class="btn btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 16px; padding: 14px 0; border-radius: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas fa-chevron-left" style="font-size: 14px; background: rgba(255,255,255,0.2); padding: 8px; border-radius: 50%;"></i>
            <span style="flex: 1; text-align: center;">Indietro</span>
        </a>
    </div>

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

    function cancelReservation(id) {
        if (confirm('Sei sicuro di voler cancellare questo appuntamento?')) {
            window.location.href = `update-status.php?id=${id}&status=cancelled`;
        }
    }
    </script>
</body>
</html>
