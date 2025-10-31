<?php
declare(strict_types=1);

require __DIR__ . '/includes/storage.php';

$config = require __DIR__ . '/includes/config.php';

$itemsDir = $config['items_path'];
$index = [];

if (is_dir($itemsDir)) {
    $files = glob($itemsDir . '/*.json');
    foreach ($files as $file) {
        $json = file_get_contents($file);
        if (!$json) {
            continue;
        }
        $item = json_decode($json, true);
        if (!is_array($item)) {
            continue;
        }
        $index[] = create_index_entry($item);
    }
}

usort($index, static function ($a, $b) {
    return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
});

save_products_index($config, $index);

echo "Index rebuilt: " . count($index) . " items\n";
