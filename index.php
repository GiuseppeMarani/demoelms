<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user's upcoming appointments
$upcoming_query = "SELECT r.*, s.name as service_name, s.duration, s.icon 
                  FROM reservations r 
                  JOIN services s ON r.service_id = s.id 
                  WHERE r.user_id = ? AND r.status = 'confirmed' 
                  AND r.reservation_date >= CURDATE() 
                  ORDER BY r.reservation_date ASC, r.reservation_time ASC 
                  LIMIT 1";
$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Barbiere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Barbiere">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="img/logo.png">
</head>
<body class="main-page">
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <img src="img/logo.gif" alt="Barbiere" class="loading-logo">
    </div>

    <!-- Header with Larger Logo -->
    <header class="header" style="padding: 40px 20px;">
        <img src="img/logo.gif" alt="Barbiere" class="header-logo" style="width: 200px; height: 200px;">
    </header>

    <!-- Dynamic Welcome Messages -->
    <div id="welcomeMessages" style="text-align: center; margin-bottom: var(--space-6); min-height: 60px;">
        <h2 class="header-subtitle animate-slide-up" style="margin-bottom: 8px;">
            Ciao, <?php echo htmlspecialchars($user['username']); ?>
        </h2>
        <div class="welcome-text animate-slide-up" style="color: var(--text-secondary); font-size: 16px;">
        </div>
    </div>

    <!-- Main Actions with CTA -->
    <div style="margin-bottom: var(--space-8); text-align: center;">
        <div style="color: var(--accent); font-weight: 500; margin-bottom: var(--space-4); animation: pulse 2s infinite;">
            Prova il nuovo modo di prenotare!
        </div>
        <a href="select-service.php" class="btn" style="margin-bottom: var(--space-4); font-size: 16px;">
            <i class="fas fa-calendar-plus"></i>
            Prenota appuntamento
        </a>
    </div>

    <!-- Upcoming Appointment -->
    <?php if ($upcoming): ?>
    <div style="margin-bottom: var(--space-8);">
        <h3 style="font-size: 16px; color: var(--text-secondary); margin-bottom: var(--space-4);">
            Prossimo appuntamento
        </h3>
        <div class="service-card">
            <div class="service-icon">
                <?php echo getServiceIcon($upcoming['icon']); ?>
            </div>
            <div class="service-info">
                <div class="service-name"><?php echo htmlspecialchars($upcoming['service_name']); ?></div>
                <div class="service-meta">
                    <span><?php echo formatDate($upcoming['reservation_date']); ?></span>
                    <span>•</span>
                    <span><?php echo formatTime($upcoming['reservation_time']); ?></span>
                </div>
            </div>
            <a href="my-reservations.php" style="color: var(--text-secondary);">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div style="margin-bottom: var(--space-8);">
        <h3 style="font-size: 16px; color: var(--text-secondary); margin-bottom: var(--space-4);">
            Azioni rapide
        </h3>
        <div class="service-card" onclick="window.location.href='my-reservations.php'" style="cursor: pointer;">
            <div class="service-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="service-info">
                <div class="service-name">I miei appuntamenti</div>
                <div class="service-meta">Visualizza tutti gli appuntamenti</div>
            </div>
            <i class="fas fa-chevron-right" style="color: var(--text-secondary);"></i>
        </div>
    </div>

    <!-- Fixed Bottom Navigation -->
    <nav class="nav">
        <a href="index.php" class="nav-link active">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="select-service.php" class="nav-link">
            <i class="fas fa-calendar-plus"></i>
            <span>Prenota</span>
        </a>
        <a href="my-reservations.php" class="nav-link">
            <i class="fas fa-calendar-alt"></i>
            <span>Appuntamenti</span>
        </a>
        <a href="profile.php" class="nav-link">
            <i class="fas fa-user"></i>
            <span>Profilo</span>
        </a>
    </nav>

    <style>
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }
    </style>

    <!-- Install Prompt -->
    <div id="installPrompt" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); width: calc(100% - 40px); max-width: 400px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <img src="img/logo.gif" alt="Barbiere" style="width: 48px; height: 48px; border-radius: 12px;">
            <div>
                <div style="font-weight: 600; margin-bottom: 4px;">Installa l'app</div>
                <div style="font-size: 14px; color: var(--text-secondary);">Aggiungi Barbiere alla tua home per un accesso rapido</div>
            </div>
        </div>
        <div style="display: flex; gap: 12px;">
            <button onclick="closeInstallPrompt()" class="btn btn-secondary" style="flex: 1;">Non ora</button>
            <button id="installButton" class="btn btn-primary" style="flex: 1;">Installa</button>
        </div>
    </div>

    <script>
    // Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(registration => console.log('ServiceWorker registered'))
                .catch(err => console.log('ServiceWorker registration failed: ', err));
        });
    }

    // Install Prompt Handler
    let deferredPrompt;
    const installPrompt = document.getElementById('installPrompt');
    const installButton = document.getElementById('installButton');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Show install prompt only if not already installed
        if (!window.matchMedia('(display-mode: standalone)').matches && !localStorage.getItem('installPromptDismissed')) {
            installPrompt.style.display = 'block';
        }
    });

    installButton.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                console.log('App installed');
            }
            deferredPrompt = null;
            installPrompt.style.display = 'none';
        }
    });

    function closeInstallPrompt() {
        installPrompt.style.display = 'none';
        localStorage.setItem('installPromptDismissed', 'true');
    }

    // Loading screen handler
    window.addEventListener('load', function() {
        const loadingScreen = document.getElementById('loadingScreen');
        setTimeout(() => {
            loadingScreen.classList.add('fade-out');
            setTimeout(() => {
                loadingScreen.style.display = 'none';
            }, 300);
        }, 1500);
    });

    // Dynamic welcome messages
    document.addEventListener('DOMContentLoaded', function() {
        const messages = [
            'Benvenuto in ELMS',
            'Pronto per un nuovo look?',
            'Prenota il tuo prossimo taglio!'
        ];
        const welcomeText = document.querySelector('.welcome-text');
        let currentIndex = 0;

        function updateMessage() {
            welcomeText.style.opacity = '0';
            setTimeout(() => {
                welcomeText.textContent = messages[currentIndex];
                welcomeText.style.opacity = '1';
                currentIndex = (currentIndex + 1) % messages.length;
            }, 500);
        }

        welcomeText.style.transition = 'opacity 0.5s ease';
        updateMessage();
        setInterval(updateMessage, 3000);

        // Check if launched in standalone mode
        if (window.matchMedia('(display-mode: standalone)').matches) {
            document.documentElement.requestFullscreen().catch(err => {
                console.log('Error attempting to enable full-screen mode:', err);
            });
        }
    });
    </script>
</body>
</html>
