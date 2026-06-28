<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> — Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: url('<?= APP_URL ?>/img/background.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(10, 60, 130, 0.55);
            z-index: 0;
        }
        .container { position: relative; z-index: 1; }
        .auth-card { max-width: 420px; border: none; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,.35); }
        .auth-logo img { height: 80px; width: 80px; object-fit: cover; border-radius: 50%; border: 3px solid #0d6efd; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card auth-card mx-auto">
                <div class="card-body p-4">
                    <?php foreach (\Core\Session::getAllFlash() as $type => $message): ?>
                        <?php $bsType = $type === 'error' ? 'danger' : $type; ?>
                        <div class="alert alert-<?= $bsType ?> py-2" role="alert"><?= $message ?></div>
                    <?php endforeach; ?>

                    <div class="text-center mb-4">
                        <div class="auth-logo mb-2">
                            <img src="<?= APP_URL ?>/img/logo.jpg" alt="City Logo">
                        </div>
                        <h4 class="fw-bold mt-2"><?= APP_NAME ?></h4>
                        <p class="text-muted small">City Health Nutrition Department</p>
                    </div>

                    <form action="<?= APP_URL ?>/login" method="post">
                        <?= \Core\Session::csrfField() ?>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" id="username" name="username" class="form-control"
                                       value="<?= htmlspecialchars($username ?? '') ?>"
                                       placeholder="Enter username" required autofocus autocomplete="username">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" id="password" name="password" class="form-control"
                                       placeholder="Enter password" required autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <p class="text-white-50 small text-center mt-3 mb-1 px-2" style="font-size:.75rem;line-height:1.5;">
                <i class="bi bi-shield-lock me-1"></i>
                This system handles confidential child nutrition data in compliance with
                <strong>Republic Act No. 10173</strong> (Data Privacy Act of 2012).
                Unauthorized access is strictly prohibited.
            </p>
            <p class="text-center text-white-50 mt-2 small"><?= APP_NAME ?> &mdash; City Health Nutrition Department</p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    const icon = this.querySelector('i');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
    icon.className = pwd.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
});
</script>
</body>
</html>
