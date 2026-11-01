<?php $pageTitle = '2FA Security'; ?>

<div class="row justify-content-center mt-3">
    <div class="col-lg-6 col-md-8">

        <h4 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2"></i>Two-Factor Authentication</h4>

        <?php if (!empty($enabled)): ?>
        <!-- 2FA is currently ON -->
        <div class="card mb-4 border-success">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="bi bi-shield-check text-success fs-2"></i>
                    <div>
                        <h6 class="mb-0 fw-bold text-success">2FA is Enabled</h6>
                        <small class="text-muted">Your account is protected with a one-time password on every login.</small>
                    </div>
                </div>
                <form action="<?= APP_URL ?>/settings/2fa/disable" method="post"
                      onsubmit="return confirm('Disable two-factor authentication? Your account will be less secure.');">
                    <?= \Core\Session::csrfField() ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-shield-x me-1"></i>Disable 2FA
                    </button>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- Setup 2FA -->
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-qr-code me-2"></i>Set Up Two-Factor Authentication
            </div>
            <div class="card-body">
                <ol class="mb-4">
                    <li class="mb-2">Install an authenticator app on your phone (Google Authenticator, Authy, or similar).</li>
                    <li class="mb-2">Scan the QR code below, <strong>or</strong> manually enter the secret key into the app.</li>
                    <li>Enter the 6-digit code shown in the app to confirm, then click <strong>Enable 2FA</strong>.</li>
                </ol>

                <!-- QR Code -->
                <div class="text-center mb-3">
                    <?php $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?data=' . rawurlencode($uri) . '&size=180x180&margin=8'; ?>
                    <img src="<?= $qrUrl ?>" alt="QR Code" width="180" height="180"
                         class="border rounded p-1"
                         onerror="this.style.display='none';document.getElementById('qr-fallback').style.display='block';">
                    <div id="qr-fallback" style="display:none;" class="alert alert-warning small mt-2">
                        QR code requires internet access. Use the secret key below to add manually.
                    </div>
                </div>

                <!-- Secret key -->
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Secret Key (manual entry)</label>
                    <div class="input-group">
                        <input type="text" id="totpSecret" class="form-control font-monospace fw-bold text-center"
                               value="<?= htmlspecialchars($secret) ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copySecret" title="Copy to clipboard">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>

                <!-- Verify + enable -->
                <form action="<?= APP_URL ?>/settings/2fa/enable" method="post" autocomplete="off">
                    <?= \Core\Session::csrfField() ?>
                    <div class="mb-3">
                        <label for="totp_code" class="form-label fw-semibold">Confirm — Enter the 6-digit code from your app</label>
                        <input type="text" id="totp_code" name="totp_code"
                               class="form-control form-control-lg font-monospace text-center fw-bold"
                               placeholder="000000"
                               maxlength="6"
                               inputmode="numeric"
                               pattern="\d{6}"
                               required
                               autofocus
                               autocomplete="one-time-code">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-shield-check me-1"></i>Enable 2FA
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <a href="<?= APP_URL ?>/users" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Users
        </a>
    </div>
</div>

<script>
document.getElementById('copySecret')?.addEventListener('click', function () {
    navigator.clipboard.writeText(document.getElementById('totpSecret').value).then(() => {
        this.innerHTML = '<i class="bi bi-clipboard-check"></i>';
        setTimeout(() => { this.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
    });
});
</script>
