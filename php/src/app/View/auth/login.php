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
    <link rel="stylesheet" href="/css/auth/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Panel -->
        <div class="left-panel">
            <h1>Nimonspedia</h1>
            <p>Nimonspedia adalah platform e-commerce berbasis web yang memungkinkan pengguna untuk berbelanja produk dari berbagai seller dan memungkinkan seller untuk mengelola toko online mereka.</p>
            <div class="decor circle">
                <span></span><span></span><span></span>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <h2 id="login-heading">Selamat Datang!</h2>
            <p>Belum punya akun? <a href="/register">Buat akun sekarang!</a></p>

            <!-- Flash Messages -->
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" id="flashMessage" role="alert" tabindex="-1">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success" id="flashMessage" role="alert" tabindex="-1">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form id="loginForm" action="/login" method="POST" role="form" aria-labelledby="login-heading">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="input-group">
                    <label for="email" class="sr-only">Email</label>
                    <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input type="email" name="email" id="email" placeholder="Masukkan Email" required
                           value="<?php echo htmlspecialchars($emailInput); ?>"
                           aria-describedby="<?php if (isset($errors['email'])): ?>email-error<?php endif; ?>">
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-message" id="email-error" role="alert"><?php echo htmlspecialchars($errors['email']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="input-group password-group">
                    <label for="password" class="sr-only">Password</label>
                    <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <circle cx="12" cy="16" r="1"></circle>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" name="password" id="password" placeholder="Masukkan Password" required
                           aria-describedby="<?php if (isset($errors['password'])): ?>password-error<?php endif; ?>">
                    <button type="button" class="password-toggle" id="passwordToggleIcon" onclick="togglePassword()" 
                            aria-label="Tampilkan password" title="Tampilkan/sembunyikan password">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                    <?php if (isset($errors['password'])): ?>
                        <span class="error-message" id="password-error" role="alert"><?php echo htmlspecialchars($errors['password']); ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="login-btn" id="submitBtn">
                    Login
                </button>
            </form>
        </div>
    </div>

    <script src="/js/auth/login.js"></script>
</body>
</html>