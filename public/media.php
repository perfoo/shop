<?php
declare(strict_types=1);

$config = require __DIR__ . '/../includes/config.php';

$id = $_GET['id'] ?? '';
$file = $_GET['file'] ?? '';

if ($id === '' || $file === '') {
    http_response_code(404);
    exit;
}

if (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
    http_response_code(404);
    exit;
}

if (!preg_match('/^[A-Za-z0-9._-]+$/', $file) || !preg_match('/\.jpe?g$/i', $file)) {
    http_response_code(404);
    exit;
}

$baseDir = realpath($config['uploads_path']);
if ($baseDir === false) {
    http_response_code(404);
    exit;
}

$path = realpath($config['uploads_path'] . '/' . $id . '/' . $file);
if ($path === false || !str_starts_with($path, $baseDir . DIRECTORY_SEPARATOR) || !is_file($path)) {
    http_response_code(404);
    exit;
}

$mime = 'image/jpeg';
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($finfo !== false) {
    $detected = finfo_file($finfo, $path);
    if (is_string($detected)) {
        $mime = $detected;
    }
    finfo_close($finfo);
}

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=604800, immutable');
readfile($path);
