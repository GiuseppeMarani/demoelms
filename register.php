<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$username || !$phone || !$email || !$password || !$confirm_password) {
        $error = 'Tutti i campi sono obbligatori';
    } elseif ($password !== $confirm_password) {
        $error = 'Le password non coincidono';
    } else {
        // Check if email or phone already exists
        $check_query = "SELECT id FROM users WHERE email = ? OR phone = ? OR username = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("sss", $email, $phone, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Email, telefono o username già registrati';
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (username, phone, email, password, role) VALUES (?, ?, ?, ?, 'customer')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ssss", $username, $phone, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['user_name'] = $username;
                header('Location: index.php');
                exit();
            } else {
                $error = 'Errore durante la registrazione';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati - Barbiere</title>
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
                Crea il tuo account
            </h2>

            <?php if ($error): ?>
                <div class="error-message" style="text-align: center; margin-bottom: var(--space-4);">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Telefono</label>
                    <input type="tel" id="phone" name="phone" class="form-input" required 
                           pattern="^\+?[0-9]{10,15}$" 
                           title="Inserisci un numero di telefono valido (10-15 cifre, può iniziare con +)">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required 
                           minlength="6" 
                           title="La password deve contenere almeno 6 caratteri">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Conferma password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>

                <button type="submit" class="btn" style="margin-top: var(--space-6);">
                    Registrati
                </button>
            </form>

            <div class="auth-footer">
                Hai già un account? <a href="login.php">Accedi</a>
            </div>
        </main>
    </div>
</body>
</html>
