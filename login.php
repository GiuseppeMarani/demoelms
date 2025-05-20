<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';  // Can be email or username
    $password = $_POST['password'] ?? '';

    if (!$login || !$password) {
        $error = 'Inserisci email/username e password';
    } else {
        // Check if login is email or username
        $query = "SELECT * FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Credenziali non valide';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi - Barbiere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <header class="header">
            <h1 class="header-title">Barbiere</h1>
        </header>

        <main class="auth-main">
            <h2 class="header-subtitle" style="text-align: center; margin-bottom: var(--space-8);">
                Accedi al tuo account
            </h2>

            <?php if ($error): ?>
                <div class="error-message" style="text-align: center; margin-bottom: var(--space-4);">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label class="form-label" for="login">Email o Username</label>
                    <input type="text" id="login" name="login" class="form-input" required 
                           autocomplete="username">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required
                           autocomplete="current-password">
                </div>

                <button type="submit" class="btn" style="margin-top: var(--space-6);">
                    Accedi
                </button>
            </form>

            <div class="auth-footer">
                Non hai un account? <a href="register.php">Registrati</a>
            </div>
        </main>
    </div>
</body>
</html>
