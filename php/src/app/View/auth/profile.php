<?php

$user = $user ?? [];
$_token = $_token ?? '';
$successMessage = $successMessage ?? null;
$errorMessage = $errorMessage ?? null;
$errors = $errors ?? [];
$errors_password = $errors_password ?? [];
$oldInput = $oldInput ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile - Nimonspedia</title>
    <link rel="stylesheet" href="/css/auth/profile.css" />
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
        <h1>Profile Saya</h1>

        <div class="profile-box">
            <h2>Form Edit Profile</h2>
            <form id="profileForm" action="/profile/update" method="POST">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_token); ?>">
                
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                </div>
                
                <div class="input-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($oldInput['name'] ?? $user['name'] ?? ''); ?>" 
                           class="<?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['name']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address" required 
                              class="<?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>"
                              ><?php echo htmlspecialchars($oldInput['address'] ?? $user['address'] ?? ''); ?></textarea>
                    <?php if (isset($errors['address'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['address']); ?></span>
                    <?php endif; ?>
                </div>               
                <button type="submit" id="profileSubmitBtn" class="btn-submit">Simpan Perubahan</button>
            </form>
        </div>

            <p class="link-center">
                <a href="/profile/password">Ubah Password Anda</a>
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