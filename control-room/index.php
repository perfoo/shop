<?php
declare(strict_types=1);

$config = require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/storage.php';
require __DIR__ . '/../includes/formatting.php';

require_https();
ensure_session($config);
require_login();

$items = load_products_index($config);
$count = count($items);
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kontrolna soba - pregled</title>
    <link rel="stylesheet" href="/public/assets/css/style.css">
</head>
<body class="admin-body">
    <header class="admin-header">
        <h1>Kontrolna soba</h1>
        <div class="admin-actions">
            <a href="/control-room/product_new.php" class="btn-primary">Dodaj novi artikl</a>
            <a href="/control-room/logout.php" class="btn-secondary">Odjava</a>
        </div>
    </header>
    <main class="admin-main">
        <section class="admin-panel">
            <h2>Stavke kataloga (<?= $count ?>)</h2>
            <?php if ($count > 60): ?>
                <div class="alert alert-warning" role="status">Upozorenje: Preko 60 artikala može usporiti upravljanje.</div>
            <?php endif; ?>
            <?php if ($count === 0): ?>
                <p>Nema unosa. Dodajte prvi artikl.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Naslov</th>
                            <th>Kategorija</th>
                            <th>Cijena</th>
                            <th>Stvoreno</th>
                            <th>Radnje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= format_price_from_cents((int)$item['price_cents']) ?></td>
                                <td><?= htmlspecialchars(date('d.m.Y. H:i', strtotime($item['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a href="/control-room/product_edit.php?id=<?= urlencode($item['id']) ?>" class="link-action">Uredi</a>
                                    <a href="/control-room/product_delete.php?id=<?= urlencode($item['id']) ?>" class="link-danger">Izbriši</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
