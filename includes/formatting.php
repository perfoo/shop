<?php
declare(strict_types=1);

/**
 * Price formatting helpers using hr-HR conventions.
 */
function parse_price_to_cents(string $input): ?int
{
    $normalized = trim($input);
    if ($normalized === '') {
        return null;
    }

    $normalized = str_replace([' ', "\u{00A0}", "\u{202F}"], '', $normalized);
    $normalized = str_replace('.', '', $normalized);
    $normalized = str_replace(',', '.', $normalized);

    if (!is_numeric($normalized)) {
        return null;
    }

    $value = round((float)$normalized * 100);
    if ($value < 0) {
        return null;
    }

    return (int)$value;
}

function format_price_from_cents(int $cents): string
{
    $euros = $cents / 100;
    return number_format($euros, 2, ',', '.') . ' €';
}

/**
 * Convert stored HTML description back to textarea-friendly plain text.
 */
function description_to_textarea(string $description): string
{
    if ($description === '') {
        return '';
    }

    $normalized = preg_replace('/<\/p>\s*<p>/i', "\n\n", $description) ?? $description;
    $normalized = str_replace(['<p>', '</p>'], ['', ''], $normalized);

    return trim(html_entity_decode($normalized, ENT_QUOTES, 'UTF-8'));
}
