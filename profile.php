<?php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if ($phone && !validatePhone($phone)) {
        $error = 'Numero di telefono non valido';
    } else {
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (password_verify($current_password, $user['password'])) {
            $updates = [];
            $types = "";
            $params = [];

            if ($phone) {
                $updates[] = "phone = ?";
                $types .= "s";
                $params[] = $phone;
            }

            if ($new_password) {
                $updates[] = "password = ?";
                $types .= "s";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            if (!empty($updates)) {
                $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
                $types .= "i";
                $params[] = $_SESSION['user_id'];

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    $success = 'Profilo aggiornato con successo';
                } else {
                    $error = 'Errore durante l\'aggiornamento';
                }
            }
        } else {
            $error = 'Password attuale non valida';
        }
    }
}

// Get user details
$query = "SELECT username, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Profilo - Barbiere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
</head>
<body class="<?php echo isBarber() ? 'dashboard-page' : 'main-page'; ?>">
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen" style="display: none;">
        <img src="img/logo.gif" alt="Barbiere" class="loading-logo">
    </div>

    <div class="app">
        <!-- Header -->
        <header class="header">
            <img src="img/logo.gif" alt="Barbiere" class="header-logo">
            <div class="header-content">
                <a href="logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 14px;">
                    <i class="fas fa-sign-out-alt"></i> Esci
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main style="padding: 20px; max-width: 600px; margin: 0 auto;">
            <?php if ($error): ?>
            <div class="error-message animate-slide-up" style="margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="success-message animate-slide-up" style="margin-bottom: 20px; background: var(--success); padding: 12px; border-radius: 12px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <!-- Profile Info -->
            <div class="card animate-slide-up">
                <div style="text-align: center; margin-bottom: 32px;">
                    <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: var(--surface-light); border-radius: 40px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user" style="font-size: 32px;"></i>
                    </div>
                    <div style="font-size: 24px; font-weight: 600;">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                    <div style="color: var(--text-secondary);">
                        <?php echo htmlspecialchars($user['phone']); ?>
                    </div>
                    <?php if (isBarber()): ?>
                    <a href="barber-dashboard.php" class="btn" style="margin-top: 16px;">
                        <i class="fas fa-cut"></i> Vai alla Dashboard
                    </a>
                    <?php endif; ?>
                </div>

                <form method="POST" novalidate>
                    <div class="input-group">
                        <label for="phone">Nuovo numero di telefono</label>
                        <input type="tel" id="phone" name="phone" class="input-field" 
                            placeholder="<?php echo htmlspecialchars($user['phone']); ?>" />
                    </div>

                    <div class="input-group">
                        <label for="current_password">Password attuale</label>
                        <div class="input-field-wrapper">
                            <input type="password" id="current_password" name="current_password" 
                                class="input-field" placeholder="Inserisci la password attuale" />
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="new_password">Nuova password (opzionale)</label>
                        <div class="input-field-wrapper">
                            <input type="password" id="new_password" name="new_password" 
                                class="input-field" placeholder="Inserisci la nuova password" />
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn" style="margin-top: 24px; position: relative; z-index: 10;">
                        Aggiorna profilo
                    </button>
                </form>
            </div>
        </main>

        <!-- Refined Back Button -->
        <div style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); width: calc(100% - 40px); max-width: 400px; z-index: 1000;">
            <a href="javascript:history.back()" class="btn btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 16px; padding: 14px 0; border-radius: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <i class="fas fa-chevron-left" style="font-size: 14px; background: rgba(255,255,255,0.2); padding: 8px; border-radius: 50%;"></i>
                <span style="flex: 1; text-align: center;">Indietro</span>
            </a>
        </div>
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

    document.addEventListener('DOMContentLoaded', function() {
        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function(e) {
            let number = e.target.value.replace(/\D/g, '');
            if (number.startsWith('39')) {
                number = '+' + number;
            } else if (!number.startsWith('+')) {
                number = '+39' + number;
            }
            e.target.value = number;
        });

        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
    </script>
</body>
</html>
