<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> — Verify Your Identity</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/bootstrap-icons.css">
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
        .otp-input { font-size: 1.6rem; letter-spacing: .3em; text-align: center; font-weight: 600; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card auth-card mx-auto">
                <div class="card-body p-4">
                    <?php foreach (\Core\Session::getAllFlash() as $type => $message): ?>
                        <?php $bsType = $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : $type); ?>
                        <div class="alert alert-<?= $bsType ?> py-2" role="alert"><?= $message ?></div>
                    <?php endforeach; ?>

                    <div class="text-center mb-4">
                        <div class="auth-logo mb-2">
                            <img src="<?= APP_URL ?>/img/logo.jpg" alt="Logo">
                        </div>
                        <h5 class="fw-bold mt-2">Verify Your Identity</h5>
                        <p class="text-muted small mb-0">
                            A 6-digit code was sent to:<br>
                            <?php foreach ($maskedEmails ?? [] as $m): ?>
                            <span class="fw-semibold d-block"><?= htmlspecialchars($m) ?></span>
                            <?php endforeach; ?>
                        </p>
                    </div>

                    <form action="<?= APP_URL ?>/otp-verify" method="post" autocomplete="off">
                        <?= \Core\Session::csrfField() ?>
                        <div class="mb-4">
                            <label for="otp_code" class="form-label fw-semibold">Verification Code</label>
                            <input type="text" id="otp_code" name="otp_code"
                                   class="form-control otp-input"
                                   placeholder="000000"
                                   maxlength="6"
                                   inputmode="numeric"
                                   pattern="\d{6}"
                                   required
                                   autofocus
                                   autocomplete="one-time-code">
                            <div class="form-text text-center">Code expires in 10 minutes</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-shield-check me-1"></i>Verify
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3 d-flex justify-content-between">
                        <a href="<?= APP_URL ?>/login" class="text-muted small">
                            <i class="bi bi-arrow-left me-1"></i>Back to login
                        </a>
                        <form action="<?= APP_URL ?>/otp-verify/resend" method="post" class="d-inline">
                            <?= \Core\Session::csrfField() ?>
                            <button type="submit" class="btn btn-link btn-sm text-muted p-0">
                                <i class="bi bi-envelope me-1"></i>Resend code
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <p class="text-center text-white-50 mt-3 small"><?= APP_NAME ?> &mdash; City Health Nutrition Department</p>
        </div>
    </div>
</div>
<script src="<?= APP_URL ?>/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('otp_code').addEventListener('input', function () {
    if (this.value.replace(/\D/g, '').length === 6) {
        this.value = this.value.replace(/\D/g, '');
        this.closest('form').submit();
    }
});
</script>
</body>
</html>
