<?php

namespace Tests\Unit\Support;

use App\Support\MoneyFormatter;
use Tests\TestCase;

class MoneyFormatterTest extends TestCase
{
    /** @test */
    public function it_formats_full_inr_correctly()
    {
        $this->assertEquals('₹0.00', MoneyFormatter::inr(0));
        $this->assertEquals('₹100.00', MoneyFormatter::inr(100));
        $this->assertEquals('₹1,000.00', MoneyFormatter::inr(1000));
        $this->assertEquals('₹10,000.00', MoneyFormatter::inr(10000));
        $this->assertEquals('₹1,00,000.00', MoneyFormatter::inr(100000));
        $this->assertEquals('₹10,00,000.00', MoneyFormatter::inr(1000000));
        $this->assertEquals('₹1,00,00,000.00', MoneyFormatter::inr(10000000));
    }

    /** @test */
    public function it_handles_negative_amounts()
    {
        $this->assertEquals('-₹100.00', MoneyFormatter::inr(-100));
        $this->assertEquals('-₹1,00,000.00', MoneyFormatter::inr(-100000));
    }

    /** @test */
    public function it_formats_compact_inr_correctly()
    {
        $this->assertEquals('₹0.00', MoneyFormatter::compactInr(0));
        $this->assertEquals('₹100.00', MoneyFormatter::compactInr(100));
        $this->assertEquals('₹1.00K', MoneyFormatter::compactInr(1000));
        $this->assertEquals('₹10.00K', MoneyFormatter::compactInr(10000));
        $this->assertEquals('₹1.00L', MoneyFormatter::compactInr(100000));
        $this->assertEquals('₹10.00L', MoneyFormatter::compactInr(1000000));
        $this->assertEquals('₹1.50Cr', MoneyFormatter::compactInr(15000000));
    }

    /** @test */
    public function it_handles_negative_compact_amounts()
    {
        $this->assertEquals('-₹1.00K', MoneyFormatter::compactInr(-1000));
        $this->assertEquals('-₹1.00L', MoneyFormatter::compactInr(-100000));
        $this->assertEquals('-₹1.50Cr', MoneyFormatter::compactInr(-15000000));
    }

    /** @test */
    public function it_formats_percentages_correctly()
    {
        $this->assertEquals('+12.5%', MoneyFormatter::percentage(12.5));
        $this->assertEquals('-5.0%', MoneyFormatter::percentage(-5));
        $this->assertEquals('+0.0%', MoneyFormatter::percentage(0));
        $this->assertEquals('+12.34%', MoneyFormatter::percentage(12.34, 2));
    }

    /** @test */
    public function it_handles_large_inr_amounts()
    {
        // 1 Crore
        $this->assertEquals('₹1,00,00,000.00', MoneyFormatter::inr(10000000));
        // 10 Crore
        $this->assertEquals('₹10,00,00,000.00', MoneyFormatter::inr(100000000));
        // 1 Lakh
        $this->assertEquals('₹1,00,000.00', MoneyFormatter::inr(100000));
    }

    /** @test */
    public function it_formats_decimal_values_correctly()
    {
        $this->assertEquals('₹150.50', MoneyFormatter::inr(150.5));
        $this->assertEquals('₹10,000.99', MoneyFormatter::inr(10000.99));
        $this->assertEquals('₹1,00,000.75', MoneyFormatter::inr(100000.75));
    }

    /** @test */
    public function compact_format_uses_inr_symbol()
    {
        $formatted = MoneyFormatter::compactInr(1000000);
        $this->assertTrue(strpos($formatted, '₹') !== false);
        $this->assertTrue(strpos($formatted, 'L') !== false);
    }

    /** @test */
    public function full_format_uses_inr_symbol()
    {
        $formatted = MoneyFormatter::inr(100000);
        $this->assertTrue(strpos($formatted, '₹') !== false);
        $this->assertTrue(strpos($formatted, ',') !== false);
    }
}
