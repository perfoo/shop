<?php
declare(strict_types=1);

$config = require __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/storage.php';
require_once __DIR__ . '/../includes/formatting.php';
require_once __DIR__ . '/../includes/validators.php';

$categoryParam = $_GET['category'] ?? 'Sve';
$sortParam = $_GET['sort'] ?? 'novo';

$allowedCategories = array_merge(['Sve'], allowed_categories());
if (!in_array($categoryParam, $allowedCategories, true)) {
    $categoryParam = 'Sve';
}

$products = load_products_index($config);

if ($categoryParam !== 'Sve') {
    $products = array_values(array_filter($products, static fn($item) => ($item['category'] ?? '') === $categoryParam));
}

if ($sortParam === 'cijena') {
    usort($products, static fn($a, $b) => ($a['price_cents'] ?? 0) <=> ($b['price_cents'] ?? 0));
} else {
    usort($products, static fn($a, $b) => strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now'));
}

$sortOptions = [
    'novo' => 'Najnovije',
    'cijena' => 'Cijena (uzlazno)',
];
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GrayShop katalog</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="/assets/js/app.js"></script>
</head>
<body>
<header class="site-header">
    <div class="logo-area">
        <span class="logo"><em>Gray.</em>Shop</span>
        <button class="menu-toggle" aria-label="Otvori izbornik" data-menu-toggle>
            <span></span><span></span><span></span>
        </button>
    </div>
    <nav class="desktop-filters" aria-label="Filtri">
        <div class="chip-group">
            <span class="chip-label">Kategorije:</span>
            <?php foreach ($allowedCategories as $category): ?>
                <a href="?category=<?= urlencode($category) ?>&sort=<?= urlencode($sortParam) ?>" class="chip <?= $categoryParam === $category ? 'chip-active' : '' ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>
        <div class="sort-control">
            <label for="sort-desktop">Sortiraj:</label>
            <select id="sort-desktop" data-sort-select>
                <?php foreach ($sortOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $sortParam === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </nav>
</header>
<div class="mobile-drawer" data-drawer aria-hidden="true">
    <div class="drawer-content">
        <button class="drawer-close" aria-label="Zatvori izbornik" data-menu-close>&times;</button>
        <div class="chip-group">
            <span class="chip-label">Kategorije:</span>
            <?php foreach ($allowedCategories as $category): ?>
                <a href="?category=<?= urlencode($category) ?>&sort=<?= urlencode($sortParam) ?>" class="chip <?= $categoryParam === $category ? 'chip-active' : '' ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>
        <div class="sort-control">
            <label for="sort-mobile">Sortiraj:</label>
            <select id="sort-mobile" data-sort-select>
                <?php foreach ($sortOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $sortParam === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
<main class="catalog">
    <?php if (empty($products)): ?>
        <p class="empty-state">Trenutno nema artikala za prikaz.</p>
    <?php else: ?>
        <section class="card-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card" data-animate>
                    <div class="card-image">
                        <?php if (!empty($product['cover'])): ?>
                            <img src="/uploads/<?= htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($product['cover'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <div class="placeholder" aria-hidden="true"></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h2><?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="card-category"><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="card-price"><?= format_price_from_cents((int)$product['price_cents']) ?></p>
                        <p class="card-excerpt"><?= htmlspecialchars($product['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="card-actions">
                            <a class="btn-link" href="/item.php?id=<?= urlencode($product['id']) ?>">Detalji</a>
                            <?php if (!empty($product['cover_image'])): ?>
                                <a class="btn-ghost" href="/item.php?id=<?= urlencode($product['id']) ?>#galerija">Galerija</a>
                            <?php endif; ?>
                            <?php if (!empty($product['external_link'])): ?>
                                <a class="btn-external" href="<?= htmlspecialchars($product['external_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Detalji - link</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> GrayShop katalog</p>
</footer>
<script>
    window.catalogState = {
        currentCategory: '<?= htmlspecialchars($categoryParam, ENT_QUOTES, 'UTF-8') ?>',
        currentSort: '<?= htmlspecialchars($sortParam, ENT_QUOTES, 'UTF-8') ?>'
    };
</script>
</body>
</html>
