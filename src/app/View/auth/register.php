<?php

$token = $_token ?? '';
$errorMessage = $errorMessage ?? null;
$successMessage = $successMessage ?? null;
$errors = $errors ?? [];
$oldInput = $oldInput ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register</title>
    <link rel="stylesheet" href="/css/register.css">
    <!-- Untuk sementara gini dulu dah icon, ntar diganti -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="register-container">
        <div class="register-box">

            <!-- Left Panel -->
            <div class="left-panel">
                <h2>INFORMASI AKUN</h2>
                
                <div class="avatar-placeholder">
                    <i class="fas fa-user"></i>
                </div>

                <!-- Registration Form (email, password, role) -->
                <form id="registerForm" action="/register" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Masukkan Email" required
                               value="<?php echo htmlspecialchars($oldInput['email'] ?? ''); ?>">
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="input-group password-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required>
                        <i class="fas fa-eye password-toggle" id="passwordToggleIcon" onclick="togglePassword('password', 'passwordToggleIcon')"></i>
                        <?php if (isset($errors['password'])): ?>
                             <span class="error-message"><?php echo htmlspecialchars($errors['password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="input-group password-group">
                        <label for="password_confirm">Konfirmasi Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi Password" required>
                        <i class="fas fa-eye password-toggle" id="confirmPasswordToggleIcon" onclick="togglePassword('password_confirm', 'confirmPasswordToggleIcon')"></i>
                        <?php if (isset($errors['password_confirm'])): ?>
                             <span class="error-message"><?php echo htmlspecialchars($errors['password_confirm']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="input-group role-selection">
                        <label>Jenis Akun:</label>
                        <div>
                            <input type="radio" id="role_buyer" name="role" value="BUYER" <?php echo (!isset($oldInput['role']) || $oldInput['role'] === 'BUYER') ? 'checked' : ''; ?>>
                            <label for="role_buyer">Buyer</label>
                        </div>
                        <div>
                            <input type="radio" id="role_seller" name="role" value="SELLER" <?php echo (isset($oldInput['role']) && $oldInput['role'] === 'SELLER') ? 'checked' : ''; ?>>
                            <label for="role_seller">Seller</label>
                        </div>
                        <?php if (isset($errors['role'])): ?>
                             <span class="error-message"><?php echo htmlspecialchars($errors['role']); ?></span>
                        <?php endif; ?>
                    </div>
            </div>

            <!-- Right Panel -->
            <div class="right-panel">
                <h2>INFORMASI PRIBADI</h2>

                <!-- Flash Messages -->
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>
                 <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                 <!-- Register Form (nama, alamat, detail toko untuk seller) -->
                    <div class="input-group">
                        <label for="name">Nama</label>
                        <input type="text" id="name" name="name" placeholder="Masukkan Nama Lengkap" required
                               value="<?php echo htmlspecialchars($oldInput['name'] ?? ''); ?>">
                        <?php if (isset($errors['name'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="input-group">
                         <label for="address">Alamat</label>
                         <textarea id="address" name="address" placeholder="Masukkan Alamat" required><?php echo htmlspecialchars($oldInput['address'] ?? ''); ?></textarea>
                    </div>

                    <fieldset id="sellerFields" style="display: none; border: none; padding: 0; margin: 0; margin-top: 20px; border-top: 1px dashed #6c757d; padding-top: 20px;">

                        <div class="input-group">
                            <label for="store_name">Nama Toko</label>
                            <input type="text" id="store_name" name="store_name" placeholder="Nama Toko (Max 100 Karakter)" maxlength="100"
                                   value="<?php echo htmlspecialchars($oldInput['store_name'] ?? ''); ?>">
                        </div>

                        <div class="input-group">
                            <label for="store_description">Deskripsi Toko</label>
                            <textarea id="store_description" name="store_description" placeholder="Deskripsi Toko Anda"><?php echo htmlspecialchars($oldInput['store_description'] ?? ''); ?></textarea>
                            </div>

                        <div class="input-group">
                             <label for="store_logo">Upload Logo Toko (Opsional, Max 2MB)</label>
                             <input type="file" id="store_logo" name="store_logo" accept="image/jpeg, image/png, image/webp">
                             </div>
                    </fieldset>

                    <button type="submit" class="register-btn" id="submitBtn">Daftar</button>

                    <p class="login-link">Sudah Punya Akun? <a href="/login">Klik di sini!</a></p>
                </form> </div>
        </div>
    </div>

    <script src="/js/register.js"></script> 
</body>
</html>