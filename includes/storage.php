<?php
declare(strict_types=1);

/**
 * Storage helpers using JSON files.
 */

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/validators.php';

/**
 * Load the products index for fast listing.
 */
function load_products_index(array $config): array
{
    if (!file_exists($config['products_index'])) {
        return [];
    }

    $json = file_get_contents($config['products_index']);
    return $json ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : [];
}

/**
 * Persist the products index using an atomic write.
 */
function save_products_index(array $config, array $index): void
{
    atomic_write($config['products_index'], json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Load a single item from disk.
 */
function load_item(array $config, string $id): ?array
{
    $path = $config['items_path'] . '/' . $id . '.json';
    if (!file_exists($path)) {
        return null;
    }

    $json = file_get_contents($path);
    return $json ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : null;
}

/**
 * Save an item definition.
 */
function save_item(array $config, string $id, array $item): void
{
    $path = $config['items_path'] . '/' . $id . '.json';
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }

    atomic_write($path, json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Delete an item and its associated images.
 */
function delete_item(array $config, string $id): void
{
    $path = $config['items_path'] . '/' . $id . '.json';
    if (file_exists($path)) {
        unlink($path);
    }

    $uploadDir = $config['uploads_path'] . '/' . $id;
    if (is_dir($uploadDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($uploadDir);
    }
}

/**
 * Remove an item from the products index and persist the change.
 */
function remove_from_index(array $config, string $id): void
{
    $index = load_products_index($config);
    $filtered = array_values(array_filter($index, static fn($entry) => ($entry['id'] ?? '') !== $id));
    save_products_index($config, $filtered);
}

/**
 * Create or update an index entry for the provided item.
 */
function upsert_index_entry(array $config, array $item): void
{
    $index = load_products_index($config);
    $entry = create_index_entry($item);
    $exists = false;
    foreach ($index as $key => $current) {
        if (($current['id'] ?? '') === $item['id']) {
            $index[$key] = $entry;
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        $index[] = $entry;
    }

    usort($index, static function ($a, $b) {
        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
    });

    save_products_index($config, $index);
}

/**
 * Generate a unique item identifier.
 */
function generate_item_id(): string
{
    $time = microtime(true);
    $base = bin2hex(random_bytes(4));
    return str_replace('.', '', sprintf('%s%f', $base, $time));
}

/**
 * Perform an atomic write with locking.
 */
function atomic_write(string $path, string $contents): void
{
    $directory = dirname($path);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $lockHandle = fopen($path . '.lock', 'c');
    if ($lockHandle === false) {
        throw new RuntimeException('Unable to create lock file for ' . $path);
    }

    try {
        if (!flock($lockHandle, LOCK_EX)) {
            throw new RuntimeException('Unable to obtain lock for ' . $path);
        }

        $temp = tempnam($directory, 'tmp');
        if ($temp === false) {
            throw new RuntimeException('Unable to create temporary file for ' . $path);
        }

        $bytesWritten = file_put_contents($temp, $contents, LOCK_EX);
        if ($bytesWritten === false) {
            throw new RuntimeException('Unable to write temporary file for ' . $path);
        }

        $tempHandle = fopen($temp, 'c');
        if ($tempHandle !== false) {
            fflush($tempHandle);
            if (function_exists('fsync')) {
                @fsync($tempHandle);
            }
            fclose($tempHandle);
        }

        if (!rename($temp, $path)) {
            unlink($temp);
            throw new RuntimeException('Unable to replace file ' . $path);
        }
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

/**
 * Build a listing-friendly representation of an item.
 */
function create_index_entry(array $item): array
{
    return [
        'id' => $item['id'],
        'title' => $item['title'],
        'price_cents' => $item['price_cents'],
        'category' => $item['category'],
        'excerpt' => create_excerpt($item['description'] ?? ''),
        'created_at' => $item['created_at'],
        'cover' => $item['photos'][0]['thumb'] ?? '',
        'cover_image' => $item['photos'][0]['image'] ?? '',
        'external_link' => $item['external_link'] ?? '',
    ];
}
