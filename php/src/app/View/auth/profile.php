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
    <title>Profile</title>
    <link rel="stylesheet" href="/css/auth/profile.css" />
    <link rel="stylesheet" href="/css/icons.css">
</head>
<body>
    
    <div class="profile-container">
        <h1>Profile Saya</h1>

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

        <p style="text-align: center; margin-top: 20px;">
            <a href="/profile/password">Ubah Password Anda</a>
        </p>

    </div>

    <script src="/js/auth/profile.js"></script>
</body>
</html>