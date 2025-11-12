<?php

$_token = $_token ?? '';
$successMessage = $successMessage ?? null;
$errorMessage = $errorMessage ?? null;
$errors = $errors ?? []; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ubah Password - Nimonspedia</title>
    <link rel="stylesheet" href="/css/auth/profile.css"> 
    <link rel="stylesheet" href="/css/buyer/base.css" />
    <link rel="stylesheet" href="/css/components/navbar-base.css" />
    <link rel="stylesheet" href="/css/components/navbar-buyer.css" />
    <link rel="stylesheet" href="/css/components/toast.css" />
    <link rel="stylesheet" href="/css/components/footer.css" />
</head>
<body>
    <?php include __DIR__ . '/../components/navbar-buyer.php'; ?>
    
    <div class="main-content" 
         <?php if (!empty($successMessage)): ?>data-success-message="<?php echo htmlspecialchars($successMessage); ?>"<?php endif; ?>
         <?php if (!empty($errorMessage)): ?>data-error-message="<?php echo htmlspecialchars($errorMessage); ?>"<?php endif; ?>>
        <div class="container">
            <div class="profile-container">
        <h1>Ubah Password</h1>

        <div class="profile-box">
            <h2>Form Ubah Password</h2>
            
            <form id="passwordForm" action="/profile/change-password" method="POST">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_token); ?>">

                <div class="input-group password-group">
                    <label for="current_password">Password Lama</label>
                    <div class="input-icon-wrapper">
                        <input type="password" id="current_password" name="current_password" required
                               class="<?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>">
                        <button type="button" class="password-toggle" id="toggleOld" onclick="togglePassword('current_password', 'toggleOld')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['current_password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['current_password']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="input-group password-group">
                    <label for="new_password">Password Baru</label>
                    <div class="input-icon-wrapper">
                        <input type="password" id="new_password" name="new_password" placeholder="Minimal 8 karakter, huruf, angka, simbol" required
                               class="<?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>">
                        <button type="button" class="password-toggle" id="toggleNew" onclick="togglePassword('new_password', 'toggleNew')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['new_password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['new_password']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group password-group">
                    <label for="new_password_confirm">Konfirmasi Password Baru</label>
                    <div class="input-icon-wrapper">
                        <input type="password" id="new_password_confirm" name="new_password_confirm" placeholder="Ulangi password baru" required
                               class="<?php echo isset($errors['new_password_confirm']) ? 'is-invalid' : ''; ?>">
                        <button type="button" class="password-toggle" id="toggleConfirm" onclick="togglePassword('new_password_confirm', 'toggleConfirm')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['new_password_confirm'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['new_password_confirm']); ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="submit" id="passwordSubmitBtn" class="btn-submit">Ganti Password</button>
            </form>
        </div>
        
            <p class="link-center">
                <a href="/profile">Kembali ke Profile</a>
            </p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>
    
    <script src="/js/components/toast.js"></script>
    <script src="/js/auth/profile.js"></script>
    <script src="/js/components/navbar-buyer.js"></script>
</body>
</html>