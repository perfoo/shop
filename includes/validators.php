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
        $errors['description'] = 'Opis je obavezan i treba koristiti <p> oznake.';
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

    $description = strip_tags($description, '<p>');
    // Remove attributes from <p> tags for safety.
    $description = preg_replace('/<p[^>]*>/i', '<p>', $description) ?? $description;

    // Ensure paragraphs are properly closed.
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
    $text = trim(strip_tags($description));
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
