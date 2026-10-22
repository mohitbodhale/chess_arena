<?php
declare(strict_types=1);

namespace App\Service\Tournament;

/**
 * The three playing formats the arena supports.
 *
 * Every contest is bound to one category. The category decides the clock and
 * is used as the live lobby filter.
 */
final class TimeControl
{
    public const BULLET = 'Bullet';
    public const BLITZ = 'Blitz';
    public const RAPID = 'Rapid';

    /**
     * Category metadata.
     *
     * @var array<string, array{label: string, base: int, increment: int, display: string, blurb: string, accent: string}>
     */
    public const CATEGORIES = [
        self::BULLET => [
            'label' => 'Bullet',
            'base' => 60,
            'increment' => 0,
            'display' => '1+0',
            'blurb' => 'Sixty seconds on the clock. Blitzkrieg chess for the fast lane.',
            'accent' => '#FF5C7A',
        ],
        self::BLITZ => [
            'label' => 'Blitz',
            'base' => 180,
            'increment' => 0,
            'display' => '3+0',
            'blurb' => 'Three minutes a side. The flagship format of the arena.',
            'accent' => '#00D09C',
        ],
        self::RAPID => [
            'label' => 'Rapid',
            'base' => 600,
            'increment' => 5,
            'display' => '10+5',
            'blurb' => 'Ten minutes with a five second increment. Deep, calculated play.',
            'accent' => '#5B8DEF',
        ],
    ];

    /**
     * All category names.
     *
     * @return array<int, string>
     */
    public static function categories(): array
    {
        return array_keys(self::CATEGORIES);
    }

    /**
     * Metadata for a category.
     *
     * @param string $category Category name.
     * @return array{label: string, base: int, increment: int, display: string, blurb: string, accent: string}
     */
    public static function meta(string $category): array
    {
        return self::CATEGORIES[$category] ?? self::CATEGORIES[self::BLITZ];
    }

    /**
     * The clock in milliseconds for a category.
     *
     * @param string $category Category name.
     * @return int
     */
    public static function baseMillis(string $category): int
    {
        return self::meta($category)['base'] * 1000;
    }

    /**
     * The increment in milliseconds for a category.
     *
     * @param string $category Category name.
     * @return int
     */
    public static function incrementMillis(string $category): int
    {
        return self::meta($category)['increment'] * 1000;
    }

    /**
     * The display notation for a category, e.g. `3+0`.
     *
     * @param string $category Category name.
     * @return string
     */
    public static function display(string $category): string
    {
        return self::meta($category)['display'];
    }

    /**
     * Parse a `3+0` style notation into base and increment seconds.
     *
     * @param string $notation Time control notation.
     * @return array{base: int, increment: int}
     */
    public static function parse(string $notation): array
    {
        if (preg_match('/^(\d+)\s*\+\s*(\d+)$/', trim($notation), $matches)) {
            return ['base' => (int)$matches[1], 'increment' => (int)$matches[2]];
        }
        if (ctype_digit(trim($notation))) {
            return ['base' => (int)trim($notation), 'increment' => 0];
        }

        return ['base' => self::meta(self::BLITZ)['base'], 'increment' => 0];
    }

    /**
     * This class is a constant holder.
     */
    private function __construct()
    {
    }
}
