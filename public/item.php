<?php
declare(strict_types=1);

$config = require __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/storage.php';
require_once __DIR__ . '/../includes/formatting.php';

$id = $_GET['id'] ?? '';
$item = $id ? load_item($config, $id) : null;

if (!$item) {
    http_response_code(404);
    echo 'Artikl nije pronađen.';
    exit;
}

$photos = $item['photos'] ?? [];
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?> - GrayShop</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="/assets/js/app.js"></script>
</head>
<body>
<header class="site-header detail-header">
    <div class="logo-area">
        <a class="logo" href="/index.php">GrayShop</a>
    </div>
    <nav class="detail-actions" aria-label="Povratak">
        <a href="/index.php" class="btn-secondary">Natrag na katalog</a>
    </nav>
</header>
<main class="item-page">
    <article class="item-card">
        <header>
            <h1><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="item-meta">
                <span class="item-category"><?= htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="item-price"><?= format_price_from_cents((int)$item['price_cents']) ?></span>
            </p>
            <?php if (!empty($item['external_link'])): ?>
                <p><a class="btn-external" href="<?= htmlspecialchars($item['external_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Detalji - link</a></p>
            <?php endif; ?>
        </header>
        <section class="item-description">
            <?= $item['description'] ?>
        </section>
        <?php if (!empty($photos)): ?>
            <section class="item-gallery" id="galerija" aria-label="Galerija fotografija">
                <div class="gallery-grid">
                    <?php foreach ($photos as $index => $photo): ?>
                        <button class="gallery-thumb" data-lightbox="<?= htmlspecialchars($photo['image'], ENT_QUOTES, 'UTF-8') ?>" data-index="<?= $index ?>">
                            <img src="/uploads/<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($photo['thumb'], ENT_QUOTES, 'UTF-8') ?>" alt="Fotografija <?= $index + 1 ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </article>
</main>
<div class="lightbox" data-lightbox-modal hidden>
    <button class="lightbox-close" aria-label="Zatvori" data-lightbox-close>&times;</button>
    <div class="lightbox-content" role="dialog" aria-modal="true">
        <img src="" alt="" data-lightbox-image>
        <button class="lightbox-prev" aria-label="Prethodna" data-lightbox-prev>&lsaquo;</button>
        <button class="lightbox-next" aria-label="Sljedeća" data-lightbox-next>&rsaquo;</button>
    </div>
</div>
<script>
    window.lightboxPhotos = <?= json_encode(array_map(static function ($photo) use ($item) {
        return '/uploads/' . $item['id'] . '/' . $photo['image'];
    }, $photos), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
</body>
</html>
