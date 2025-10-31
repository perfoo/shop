<?php
declare(strict_types=1);

require_once __DIR__ . '/formatting.php';

/**
 * Validate and sanitize item form input.
 *
 * @return array{data: array, errors: array}
 */
function validate_item_input(array $input): array
{
    $errors = [];
    $data = [];

    $title = trim($input['title'] ?? '');
    if ($title === '') {
        $errors['title'] = 'Naslov je obavezan.';
    }
    $data['title'] = $title;

    $description = sanitize_description($input['description'] ?? '');
    if ($description === '') {
        $errors['description'] = 'Opis je obavezan.';
    }
    $data['description'] = $description;

    $priceInput = trim($input['price'] ?? '');
    $priceCents = parse_price_to_cents($priceInput);
    if ($priceCents === null) {
        $errors['price'] = 'Cijena mora biti pozitivan broj.';
    }
    $data['price_cents'] = $priceCents ?? 0;

    $category = $input['category'] ?? '';
    if (!in_array($category, allowed_categories(), true)) {
        $errors['category'] = 'Nevažeća kategorija.';
    }
    $data['category'] = $category;

    $external = trim($input['external_link'] ?? '');
    if ($external !== '') {
        if (!filter_var($external, FILTER_VALIDATE_URL)) {
            $errors['external_link'] = 'Vanjska poveznica mora biti ispravna URL adresa.';
        } else {
            $data['external_link'] = $external;
        }
    } else {
        $data['external_link'] = '';
    }

    return ['data' => $data, 'errors' => $errors];
}

/**
 * Keep only allowed HTML in the description.
 */
function sanitize_description(string $description): string
{
    $description = trim($description);
    if ($description === '') {
        return '';
    }

    $containsHtml = str_contains($description, '<');

    if (!$containsHtml) {
        $paragraphs = preg_split('/\r\n|\r|\n/', $description) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), static fn($line) => $line !== ''));

        if (empty($paragraphs)) {
            return '';
        }

        return implode('', array_map(static fn($line) => '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>', $paragraphs));
    }

    $description = strip_tags($description, '<p>');
    $description = preg_replace('/<p[^>]*>/i', '<p>', $description) ?? $description;
    $description = trim($description);

    if ($description === '') {
        return '';
    }

    if (!str_contains($description, '<p>')) {
        return '<p>' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    if (substr_count($description, '<p>') !== substr_count($description, '</p>')) {
        $description .= '</p>';
    }

    return $description;
}

/**
 * Generate a short excerpt from HTML content.
 */
function create_excerpt(string $description, int $limit = 160): string
{
    $text = trim(html_entity_decode(strip_tags($description), ENT_QUOTES, 'UTF-8'));
    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    $excerpt = mb_substr($text, 0, $limit - 1);
    return rtrim($excerpt) . '…';
}

/**
 * List of allowed categories.
 */
function allowed_categories(): array
{
    return ['Mobiteli', 'Informatika', 'Audio-video', 'Ostalo'];
}
