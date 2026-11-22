<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Commercial\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_rounds_half_up_without_float_drift(): void
    {
        $this->assertSame(1.01, Money::round(1.005));
        $this->assertSame(2.68, Money::round(2.675));
        $this->assertSame(0.3, Money::sum(0.1, 0.2));
        $this->assertSame(100.05, Money::round('100.049999999'.'9'));
    }

    public function test_itbis_is_computed_in_whole_cents(): void
    {
        $this->assertSame(18.0, Money::percentage(100, 18));
        $this->assertSame(18.01, Money::percentage(100.05, 18));   // 18.009
        $this->assertSame(0.09, Money::percentage(0.5, 18));        // 0.09 exacto
        $this->assertSame(0.0, Money::percentage(0, 18));
        $this->assertSame(1800.0, Money::percentage(10000, 18));
    }

    public function test_total_adds_in_cents(): void
    {
        $subtotal = Money::round(3 * 33.35);
        $taxes = Money::percentage($subtotal, 18);

        $this->assertSame(100.05, $subtotal);
        $this->assertSame(118.06, Money::sum($subtotal, $taxes));
    }
}
