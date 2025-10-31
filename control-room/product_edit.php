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

$id = $_GET['id'] ?? '';
$item = $id ? load_item($config, $id) : null;

if (!$item) {
    http_response_code(404);
    echo 'Artikl nije pronađen.';
    exit;
}

$errors = [];
$formData = [
    'title' => $item['title'],
    'description' => $item['description'],
    'price' => format_price_from_cents((int)$item['price_cents']),
    'category' => $item['category'],
    'external_link' => $item['external_link'] ?? '',
];
$existingPhotos = $item['photos'] ?? [];

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

        $removeList = array_map('strval', $_POST['remove_photos'] ?? []);
        $order = $_POST['photo_order'] ?? [];
        $files = normalize_files_array($_FILES['photos'] ?? []);
        $finalPhotos = [];
        $storedNew = [];

        if (empty($order)) {
            foreach ($existingPhotos as $photo) {
                if (in_array($photo['image'], $removeList, true)) {
                    continue;
                }
                $order[] = 'existing:' . $photo['image'];
            }
            foreach (array_keys($files) as $idx) {
                $order[] = 'new:' . $idx;
            }
        }

        if (empty($errors)) {
            $existingMap = [];
            foreach ($existingPhotos as $photo) {
                $existingMap[$photo['image']] = $photo;
            }

            try {
                foreach ($order as $entry) {
                    if (str_starts_with($entry, 'existing:')) {
                        $key = substr($entry, 9);
                        if (in_array($key, $removeList, true)) {
                            continue;
                        }
                        if (isset($existingMap[$key])) {
                            $finalPhotos[] = $existingMap[$key];
                        }
                    } elseif (str_starts_with($entry, 'new:')) {
                        $index = (int)substr($entry, 4);
                        if (!isset($files[$index])) {
                            continue;
                        }
                        if (($files[$index]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        $photo = store_photo($config, $id, $files[$index]);
                        $finalPhotos[] = $photo;
                        $storedNew[] = $photo;
                    }
                }

                if (count($finalPhotos) < 1) {
                    $errors['photos'] = 'Potrebna je najmanje jedna fotografija.';
                }
                if (count($finalPhotos) > 3) {
                    $errors['photos'] = 'Maksimalno tri fotografije.';
                }
            } catch (Throwable $e) {
                $errors['photos'] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            foreach ($storedNew as $photo) {
                remove_photo_files($config, $id, $photo);
            }
        } else {
            // Remove deleted photos from disk.
            foreach ($existingPhotos as $photo) {
                if (in_array($photo['image'], $removeList, true)) {
                    remove_photo_files($config, $id, $photo);
                }
            }

            $item['title'] = $validation['data']['title'];
            $item['description'] = $validation['data']['description'];
            $item['price_cents'] = $validation['data']['price_cents'];
            $item['category'] = $validation['data']['category'];
            $item['external_link'] = $validation['data']['external_link'];
            $item['updated_at'] = date('c');
            $item['photos'] = $finalPhotos;

            save_item($config, $id, $item);
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
    <title>Uredi artikl</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="/assets/js/app.js"></script>
</head>
<body class="admin-body">
<header class="admin-header">
    <h1>Uredi artikl</h1>
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
            <label>Fotografije</label>
            <?php if (isset($errors['photos'])): ?><span class="form-error"><?= htmlspecialchars($errors['photos'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            <div class="photo-sorter" data-sorter>
                <?php foreach ($existingPhotos as $photo): ?>
                    <div class="photo-slot" draggable="true">
                        <input type="hidden" name="photo_order[]" value="existing:<?= htmlspecialchars($photo['image'], ENT_QUOTES, 'UTF-8') ?>">
                        <figure>
                            <img src="/uploads/<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($photo['thumb'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                            <figcaption><?= htmlspecialchars($photo['image'], ENT_QUOTES, 'UTF-8') ?></figcaption>
                        </figure>
                        <label class="remove-check">
                            <input type="checkbox" name="remove_photos[]" value="<?= htmlspecialchars($photo['image'], ENT_QUOTES, 'UTF-8') ?>">
                            Ukloni
                        </label>
                    </div>
                <?php endforeach; ?>
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="photo-slot" draggable="true">
                        <input type="hidden" name="photo_order[]" value="new:<?= $i ?>">
                        <label>Nova datoteka <?= $i + 1 ?>
                            <input type="file" name="photos[]" accept="image/jpeg,image/png">
                        </label>
                    </div>
                <?php endfor; ?>
            </div>
            <p class="form-help">Povucite za promjenu redoslijeda. Označite za uklanjanje fotografije.</p>
        </div>
        <div class="form-actions form-field-full">
            <button type="submit" class="btn-primary">Spremi promjene</button>
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
?>
