<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="text-center">
        <div class="display-1 fw-bold text-primary mb-3">404</div>
        <h2>Page Not Found</h2>
        <p class="text-muted">The page you are looking for does not exist or has been moved.</p>
        <a href="<?= defined('APP_URL') ? APP_URL : '/' ?>/dashboard" class="btn btn-primary mt-2">
            <i class="bi bi-house me-1"></i>Go to Dashboard
        </a>
    </div>
</body>
</html>
