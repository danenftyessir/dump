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
    <title>Ubah Password</title>
    <link rel="stylesheet" href="/css/auth/profile.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    
    <div class="profile-container">
        <h1>Ubah Password</h1>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="profile-box">
            <h2>Form Ubah Password</h2>
            
            <form id="passwordForm" action="/profile/change-password" method="POST">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_token); ?>">

                <div class="input-group password-group">
                    <label for="current_password">Password Lama</label>
                    <input type="password" id="current_password" name="current_password" required
                           class="<?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>">
                    <i class="fas fa-eye password-toggle" id="toggleOld" onclick="togglePassword('current_password', 'toggleOld')"></i>
                    <?php if (isset($errors['current_password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['current_password']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="input-group password-group">
                    <label for="new_password">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Minimal 8 karakter, huruf, angka, simbol" required
                           class="<?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>">
                    <i class="fas fa-eye password-toggle" id="toggleNew" onclick="togglePassword('new_password', 'toggleNew')"></i>
                    <?php if (isset($errors['new_password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['new_password']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group password-group">
                    <label for="new_password_confirm">Konfirmasi Password Baru</label>
                    <input type="password" id="new_password_confirm" name="new_password_confirm" placeholder="Ulangi password baru" required
                           class="<?php echo isset($errors['new_password_confirm']) ? 'is-invalid' : ''; ?>">
                    <i class="fas fa-eye password-toggle" id="toggleConfirm" onclick="togglePassword('new_password_confirm', 'toggleConfirm')"></i>
                    <?php if (isset($errors['new_password_confirm'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['new_password_confirm']); ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="submit" id="passwordSubmitBtn" class="btn-submit">Ganti Password</button>
            </form>
        </div>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="/profile">Kembali ke Profile</a>
        </p>

    </div>

    <script src="/js/auth/profile.js"></script>
</body>
</html>