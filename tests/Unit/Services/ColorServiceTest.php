<?php

namespace Tests\Unit\Services;

use App\Services\ColorService;
use Tests\TestCase;

class ColorServiceTest extends TestCase
{
    public function test_hex_validation()
    {
        $this->assertTrue(ColorService::isValidHex('#0a5c42'));
        $this->assertTrue(ColorService::isValidHex('#FFFFFF'));
        $this->assertFalse(ColorService::isValidHex('0a5c42')); // missing #
        $this->assertFalse(ColorService::isValidHex('#0a5c4')); // too short
        $this->assertFalse(ColorService::isValidHex('#0a5c422')); // too long
        $this->assertFalse(ColorService::isValidHex('#0a5c4z')); // invalid character
    }

    public function test_hex_to_rgb_conversion()
    {
        $rgb = ColorService::hexToRgb('#0a5c42');
        $this->assertEquals(['r' => 10, 'g' => 92, 'b' => 66], $rgb);

        $rgb = ColorService::hexToRgb('#fff');
        $this->assertEquals(['r' => 255, 'g' => 255, 'b' => 255], $rgb);
    }

    public function test_luminance_calculation()
    {
        // White should have high luminance (exactly 1.0)
        $lum = ColorService::calculateLuminance(ColorService::hexToRgb('#ffffff'));
        $this->assertEquals(1.0, $lum);

        // Black should have low luminance (exactly 0.0)
        $lum = ColorService::calculateLuminance(ColorService::hexToRgb('#000000'));
        $this->assertEquals(0.0, $lum);

        // Middle gray #808080 should be around 0.215
        $lum = ColorService::calculateLuminance(ColorService::hexToRgb('#808080'));
        $this->assertGreaterThan(0.2, $lum);
        $this->assertLessThan(0.25, $lum);
    }

    public function test_contrast_calculation()
    {
        // Black on white should have maximum contrast
        $contrast = ColorService::calculateContrast('#000000', '#ffffff');
        $this->assertEquals(21, $contrast);

        // White on black should also have maximum contrast
        $contrast = ColorService::calculateContrast('#ffffff', '#000000');
        $this->assertEquals(21, $contrast);

        // Same colors should have minimum contrast
        $contrast = ColorService::calculateContrast('#0a5c42', '#0a5c42');
        $this->assertEquals(1, $contrast);
    }

    public function test_contrasting_color()
    {
        // Light background should get black text
        $this->assertEquals('#000000', ColorService::getContrastingColor('#ffffff'));

        // Dark background should get white text
        $this->assertEquals('#ffffff', ColorService::getContrastingColor('#000000'));

        // Middle color
        $this->assertEquals('#000000', ColorService::getContrastingColor('#808080'));
    }

    public function test_contrast_standards()
    {
        // Black on white should pass AA for normal and large text
        $this->assertTrue(ColorService::meetsContrastStandard('#000000', '#ffffff'));
        $this->assertTrue(ColorService::meetsContrastStandard('#000000', '#ffffff', true));

        // White on black should pass AA for normal and large text
        $this->assertTrue(ColorService::meetsContrastStandard('#ffffff', '#000000'));
        $this->assertTrue(ColorService::meetsContrastStandard('#ffffff', '#000000', true));

        // Low contrast combination should fail for normal text but pass for large text
        $this->assertFalse(ColorService::meetsContrastStandard('#7f7f7f', '#ffffff')); // gray on white, normal text
        $this->assertTrue(ColorService::meetsContrastStandard('#7f7f7f', '#ffffff', true)); // gray on white, large text

        // Very low contrast should fail both
        $this->assertFalse(ColorService::meetsContrastStandard('#cccccc', '#ffffff')); // light gray on white
        $this->assertFalse(ColorService::meetsContrastStandard('#cccccc', '#ffffff', true)); // light gray on white, large text
    }

    public function test_semantic_foreground()
    {
        // For a blue background, should get white or black text depending on shade
        $fg = ColorService::getSemanticForeground('#0066cc'); // medium blue
        $this->assertContains($fg, ['#000000', '#ffffff']);

        // For a very light yellow, should get black
        $fg = ColorService::getSemanticForeground('#ffff99');
        $this->assertEquals('#000000', $fg);

        // For a very dark purple, should get white
        $fg = ColorService::getSemanticForeground('#330066');
        $this->assertEquals('#ffffff', $fg);
    }
}
