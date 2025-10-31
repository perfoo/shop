<?php
declare(strict_types=1);

$config = require __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/storage.php';

require_https();
ensure_session($config);
require_login();

$id = $_GET['id'] ?? '';
$item = $id ? load_item($config, $id) : null;
$error = '';

if (!$item) {
    http_response_code(404);
    echo 'Artikl nije pronađen.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Sigurnosna provjera nije uspjela.';
    } else {
        delete_item($config, $id);
        remove_from_index($config, $id);
        header('Location: /control-room/index.php');
        exit;
    }
}

$token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Izbriši artikl</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<header class="admin-header">
    <h1>Izbriši artikl</h1>
</header>
<main class="admin-main">
    <section class="confirm-card">
        <h2><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Jeste li sigurni da želite trajno izbrisati ovaj artikl? Fotografije će biti uklonjene.</p>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn-danger">Da, izbriši</button>
            <a href="/control-room/index.php" class="btn-secondary">Odustani</a>
        </form>
    </section>
</main>
</body>
</html>
