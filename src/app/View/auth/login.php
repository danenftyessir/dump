<?php

$token = $_token ?? '';
$errorMessage = $errorMessage ?? null;
$successMessage = $successMessage ?? null;
$errors = $errors ?? [];
$oldInput = $oldInput ?? [];
$emailInput = $oldInput['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link rel="stylesheet" href="/css/login.css">
    <!-- Untuk sementara gini dulu dah icon, ntar diganti -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Panel -->
        <div class="left-panel">
            <h1>Nimonspedia</h1>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus sit amet dolor tellus. Quisque mattis.</p>
            <div class="decor circle">
                <span></span><span></span><span></span>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <h2>Selamat Datang!</h2>
            <p>Belum punya akun? <a href="/register">Buat akun sekarang!</a></p>

            <!-- Flash Messages -->
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" id="flashMessage">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success" id="flashMessage">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form id="loginForm" action="/login" method="POST">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="input-group">
                    <i class="fas fa-envelope icon"></i>
                    <input type="email" name="email" placeholder="Masukkan Email Anda" required
                           value="<?php echo htmlspecialchars($emailInput); ?>">
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['email']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="input-group password-group">
                    <i class="fas fa-lock icon"></i>
                    <input type="password" name="password" id="password" placeholder="Masukkan Kata Sandi" required>
                    <i class="fas fa-eye password-toggle" id="passwordToggleIcon" onclick="togglePassword()"></i>
                    <?php if (isset($errors['password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['password']); ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="login-btn" id="submitBtn">
                    Login
                </button>
            </form>
        </div>
    </div>

    <script src="/js/login.js"></script> 
</body>
</html>