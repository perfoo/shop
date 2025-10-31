<?php
declare(strict_types=1);

$config = require __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_https();
ensure_session($config);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Sigurnosna provjera nije uspjela. Pokušajte ponovno.';
    } else {
        $password = $_POST['password'] ?? '';
        if (attempt_login($password, $config)) {
            header('Location: /control-room/index.php');
            exit;
        }
        $error = 'Pogrešna lozinka.';
    }
}

if (is_logged_in()) {
    header('Location: /control-room/index.php');
    exit;
}

$token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kontrolna soba - prijava</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
    <main class="auth-card" aria-labelledby="auth-title">
        <h1 id="auth-title">Kontrolna soba</h1>
        <p>Unesite lozinku za pristup administraciji.</p>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="" class="form-stacked">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <label for="password">Lozinka</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button type="submit" class="btn-primary">Prijava</button>
        </form>
    </main>
</body>
</html>
