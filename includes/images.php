<?php
declare(strict_types=1);

/**
 * Image processing helpers using GD.
 */

$config = require __DIR__ . '/config.php';

function store_photo(array $config, string $itemId, array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Pogreška pri prijenosu slike.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Slika mora biti manja od 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Podržane su samo JPEG ili PNG slike.');
    }

    $extension = $allowed[$mime];
    $safeName = bin2hex(random_bytes(8));
    $targetName = 'photo_' . $safeName . '.jpg';
    $thumbName = 'thumb_' . $safeName . '.jpg';

    [$width, $height] = getimagesize($file['tmp_name']);
    if ($width < 500 || $height < 500) {
        throw new RuntimeException('Slika mora biti najmanje 500x500 px.');
    }

    $source = create_image_from_file($file['tmp_name'], $extension);
    if (!$source) {
        throw new RuntimeException('Slika se ne može pročitati.');
    }

    $uploadDir = $config['uploads_path'] . '/' . $itemId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $targetPath = $uploadDir . '/' . $targetName;
    $thumbPath = $uploadDir . '/' . $thumbName;

    $targetSize = calculate_target_size($width, $height);
    $resized = resize_and_crop($source, $width, $height, $targetSize['width'], $targetSize['height']);
    save_jpeg($resized, $targetPath);

    $thumbResized = create_thumbnail($resized, $targetSize['width'], $targetSize['height']);
    save_jpeg($thumbResized, $thumbPath);

    imagedestroy($source);
    imagedestroy($resized);
    imagedestroy($thumbResized);

    return ['image' => $targetName, 'thumb' => $thumbName];
}

function create_image_from_file(string $path, string $extension)
{
    return match ($extension) {
        'jpg', 'jpeg' => imagecreatefromjpeg($path),
        'png' => imagecreatefrompng($path),
        default => null,
    };
}

function calculate_target_size(int $width, int $height): array
{
    if ($width >= $height) {
        return ['width' => 1024, 'height' => 768];
    }
    return ['width' => 768, 'height' => 1024];
}

function resize_and_crop($source, int $srcWidth, int $srcHeight, int $targetWidth, int $targetHeight)
{
    $srcAspect = $srcWidth / $srcHeight;
    $targetAspect = $targetWidth / $targetHeight;

    if ($srcAspect > $targetAspect) {
        $scaleHeight = $targetHeight;
        $scaleWidth = (int)round($targetHeight * $srcAspect);
    } else {
        $scaleWidth = $targetWidth;
        $scaleHeight = (int)round($targetWidth / $srcAspect);
    }

    $scaled = imagecreatetruecolor($scaleWidth, $scaleHeight);
    $white = imagecolorallocate($scaled, 255, 255, 255);
    imagefill($scaled, 0, 0, $white);
    imagecopyresampled($scaled, $source, 0, 0, 0, 0, $scaleWidth, $scaleHeight, $srcWidth, $srcHeight);

    $x = (int)max(0, ($scaleWidth - $targetWidth) / 2);
    $y = (int)max(0, ($scaleHeight - $targetHeight) / 2);

    $cropped = imagecreatetruecolor($targetWidth, $targetHeight);
    $whiteCrop = imagecolorallocate($cropped, 255, 255, 255);
    imagefill($cropped, 0, 0, $whiteCrop);
    imagecopy($cropped, $scaled, 0, 0, $x, $y, $targetWidth, $targetHeight);
    imagedestroy($scaled);

    return $cropped;
}

function create_thumbnail($image, int $width, int $height)
{
    $longSide = max($width, $height);
    $ratio = 400 / $longSide;
    $thumbWidth = (int)round($width * $ratio);
    $thumbHeight = (int)round($height * $ratio);

    $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
    $white = imagecolorallocate($thumbnail, 255, 255, 255);
    imagefill($thumbnail, 0, 0, $white);
    imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

    return $thumbnail;
}

function save_jpeg($image, string $path): void
{
    imageinterlace($image, true);
    imagejpeg($image, $path, 85);
}

function remove_photo_files(array $config, string $itemId, array $photo): void
{
    $dir = $config['uploads_path'] . '/' . $itemId;
    foreach (['image', 'thumb'] as $key) {
        if (!empty($photo[$key])) {
            $file = $dir . '/' . $photo[$key];
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
