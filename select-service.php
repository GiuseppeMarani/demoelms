<?php
require_once 'config.php';
requireLogin();

$services_query = "SELECT * FROM services ORDER BY id ASC";
$services_result = $conn->query($services_query);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scegli Servizio - Barbiere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="index.php" style="text-decoration: none; color: var(--text);">
            <i class="fas fa-arrow-left" style="font-size: 20px;"></i>
        </a>
        <h1 class="header-title">Prenota</h1>
    </header>

    <!-- Progress -->
    <div style="margin-bottom: var(--space-6);">
        <div style="color: var(--text-secondary); font-size: 14px; margin-bottom: var(--space-2);">
            Passo 1 di 4
        </div>
        <div style="height: 4px; background: var(--disabled); border-radius: var(--radius-sm);">
            <div style="width: 25%; height: 100%; background: var(--accent); border-radius: var(--radius-sm);"></div>
        </div>
    </div>

    <h2 class="header-subtitle">Scegli il servizio</h2>

    <!-- Services List -->
    <div class="service-list">
        <?php while ($service = $services_result->fetch_assoc()): ?>
        <a href="select-date.php?service=<?php echo $service['id']; ?>" class="service-card">
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
            <i class="fas fa-chevron-right" style="color: var(--text-secondary);"></i>
        </a>
        <?php endwhile; ?>
    </div>

    <!-- Info Card -->
    <div style="text-align: center; padding: var(--space-4); border: 1px solid var(--border); border-radius: var(--radius-md); margin-top: var(--space-6);">
        <i class="fas fa-info-circle" style="color: var(--text-secondary); font-size: 20px; margin-bottom: var(--space-3);"></i>
        <p style="color: var(--text-secondary); font-size: 14px;">
            Seleziona il servizio desiderato per procedere con la prenotazione
        </p>
    </div>

    <!-- Fixed Bottom Navigation -->
    <nav class="nav">
        <a href="index.php" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="select-service.php" class="nav-link active">
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
</body>
</html>
