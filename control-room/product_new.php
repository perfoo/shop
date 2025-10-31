<?php
declare(strict_types=1);

$config = require __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/storage.php';
require_once __DIR__ . '/../includes/validators.php';
require_once __DIR__ . '/../includes/images.php';
require_once __DIR__ . '/../includes/formatting.php';

require_https();
ensure_session($config);
require_login();

$errors = [];
$formData = [
    'title' => '',
    'description' => '<p></p>',
    'price' => '',
    'category' => allowed_categories()[0],
    'external_link' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Sigurnosna provjera nije uspjela.';
    } else {
        $validation = validate_item_input($_POST);
        $formData = [
            'title' => $validation['data']['title'],
            'description' => $validation['data']['description'],
            'price' => $_POST['price'] ?? '',
            'category' => $validation['data']['category'],
            'external_link' => $validation['data']['external_link'],
        ];
        $errors = array_merge($errors, $validation['errors']);

        $storedPhotos = [];
        $itemId = '';

        if (empty($errors)) {
            $order = $_POST['photo_order'] ?? [];
            $files = normalize_files_array($_FILES['photos'] ?? []);
            if (empty($order)) {
                foreach (array_keys($files) as $idx) {
                    $order[] = 'new:' . $idx;
                }
            }

            $itemId = generate_item_id();
            try {
                foreach ($order as $entry) {
                    if (!str_starts_with($entry, 'new:')) {
                        continue;
                    }
                    $index = (int)substr($entry, 4);
                    if (!isset($files[$index])) {
                        continue;
                    }
                    if (($files[$index]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $storedPhotos[] = store_photo($config, $itemId, $files[$index]);
                }

                if (count($storedPhotos) < 1) {
                    $errors['photos'] = 'Potrebna je najmanje jedna fotografija.';
                }
                if (count($storedPhotos) > 3) {
                    $errors['photos'] = 'Maksimalno tri fotografije.';
                }
            } catch (Throwable $e) {
                $errors['photos'] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            if ($itemId !== '') {
                cleanup_photos($config, $itemId, $storedPhotos);
            }
        } else {
            $item = [
                'id' => $itemId,
                'title' => $validation['data']['title'],
                'description' => $validation['data']['description'],
                'price_cents' => $validation['data']['price_cents'],
                'category' => $validation['data']['category'],
                'external_link' => $validation['data']['external_link'],
                'created_at' => date('c'),
                'updated_at' => date('c'),
                'photos' => $storedPhotos,
            ];

            save_item($config, $itemId, $item);
            upsert_index_entry($config, $item);

            header('Location: /control-room/index.php');
            exit;
        }
    }
}

$token = get_csrf_token();
$categories = allowed_categories();
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novi artikl</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="/assets/js/app.js"></script>
</head>
<body class="admin-body">
<header class="admin-header">
    <h1>Novi artikl</h1>
    <div class="admin-actions">
        <a href="/control-room/index.php" class="btn-secondary">Povratak</a>
    </div>
</header>
<main class="admin-main">
    <form method="post" enctype="multipart/form-data" class="form-grid" data-photo-form>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-field">
            <label for="title">Naslov</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($formData['title'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['title'])): ?><span class="form-error"><?= htmlspecialchars($errors['title'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field">
            <label for="price">Cijena</label>
            <input type="text" id="price" name="price" required value="<?= htmlspecialchars($formData['price'], ENT_QUOTES, 'UTF-8') ?>" placeholder="1.234,56">
            <?php if (isset($errors['price'])): ?><span class="form-error"><?= htmlspecialchars($errors['price'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field">
            <label for="category">Kategorija</label>
            <select id="category" name="category" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" <?= $category === $formData['category'] ? 'selected' : '' ?>><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category'])): ?><span class="form-error"><?= htmlspecialchars($errors['category'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field-full">
            <label for="description">Opis (koristite &lt;p&gt; blokove)</label>
            <textarea id="description" name="description" rows="8" required><?= htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (isset($errors['description'])): ?><span class="form-error"><?= htmlspecialchars($errors['description'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field-full">
            <label for="external_link">Vanjska poveznica</label>
            <input type="url" id="external_link" name="external_link" value="<?= htmlspecialchars($formData['external_link'], ENT_QUOTES, 'UTF-8') ?>" placeholder="https://...">
            <?php if (isset($errors['external_link'])): ?><span class="form-error"><?= htmlspecialchars($errors['external_link'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field-full">
            <label>Fotografije (1-3)</label>
            <?php if (isset($errors['photos'])): ?><span class="form-error"><?= htmlspecialchars($errors['photos'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            <div class="photo-sorter" data-sorter>
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="photo-slot" draggable="true">
                        <input type="hidden" name="photo_order[]" value="new:<?= $i ?>">
                        <label>Datoteka <?= $i + 1 ?>
                            <input type="file" name="photos[]" accept="image/jpeg,image/png" <?= $i === 0 ? 'required' : '' ?>>
                        </label>
                    </div>
                <?php endfor; ?>
            </div>
            <p class="form-help">Povucite redoslijed za odabir naslovne fotografije.</p>
        </div>
        <div class="form-actions form-field-full">
            <button type="submit" class="btn-primary">Spremi artikl</button>
        </div>
    </form>
</main>
</body>
</html>
<?php
function normalize_files_array(array $files): array
{
    $normalized = [];
    if (empty($files) || !isset($files['name'])) {
        return $normalized;
    }

    foreach ($files['name'] as $index => $name) {
        $normalized[$index] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $normalized;
}

function cleanup_photos(array $config, string $itemId, array $photos): void
{
    $dir = $config['uploads_path'] . '/' . $itemId;
    foreach ($photos as $photo) {
        remove_photo_files($config, $itemId, $photo);
    }
    if (is_dir($dir)) {
        @rmdir($dir);
    }
}
?>
