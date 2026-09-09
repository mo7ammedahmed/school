<?php

namespace App\Services;

class ColorService
{
    /**
     * Validate that a hex color string is valid
     */
    public static function isValidHex(string $color): bool
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1;
    }

    /**
     * Convert hex color to RGB array
     */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) .
                   str_repeat(substr($hex, 1, 1), 2) .
                   str_repeat(substr($hex, 2, 1), 2);
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Calculate luminance of a color based on RGB values
     * Returns a value between 0 and 1
     */
    public static function calculateLuminance(array $rgb): float
    {
        $rsrgb = $rgb['r'] / 255;
        $gsrgb = $rgb['g'] / 255;
        $bsrgb = $rgb['b'] / 255;

        $r = ($rsrgb <= 0.03928)
            ? $rsrgb / 12.92
            : pow(($rsrgb + 0.055) / 1.055, 2.4);

        $g = ($gsrgb <= 0.03928)
            ? $gsrgb / 12.92
            : pow(($gsrgb + 0.055) / 1.055, 2.4);

        $b = ($bsrgb <= 0.03928)
            ? $bsrgb / 12.92
            : pow(($bsrgb + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Calculate contrast ratio between two colors
     * Returns a value between 1 and 21
     */
    public static function calculateContrast(string $color1, string $color2): float
    {
        $lum1 = self::calculateLuminance(self::hexToRgb($color1));
        $lum2 = self::calculateLuminance(self::hexToRgb($color2));

        $lumMax = max($lum1, $lum2);
        $lumMin = min($lum1, $lum2);

        return ($lumMax + 0.05) / ($lumMin + 0.05);
    }

    /**
     * Get contrasting color (black or white) for a given background color
     * Returns the color that provides better readability
     */
    public static function getContrastingColor(string $hexColor): string
    {
        $lum = self::calculateLuminance(self::hexToRgb($hexColor));
        return ($lum > 0.179) ? '#000000' : '#ffffff';
    }

    /**
     * Validate that two colors meet WCAG AA contrast requirements
     * For normal text: contrast ratio >= 4.5
     * For large text: contrast ratio >= 3.0
     */
    public static function meetsContrastStandard(string $foreground, string $background, bool $isLargeText = false): bool
    {
        $contrast = self::calculateContrast($foreground, $background);
        $required = $isLargeText ? 3.0 : 4.5;
        return $contrast >= $required;
    }

    /**
     * Generate a semantic color palette from a base color
     * Returns foreground color that provides good contrast
     */
    public static function getSemanticForeground(string $backgroundColor): string
    {
        return self::getContrastingColor($backgroundColor);
    }
}