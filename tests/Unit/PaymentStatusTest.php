<?php

namespace Tests\Unit;

use App\Support\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_nothing_owed_is_always_paid_even_with_no_payments(): void
    {
        $this->assertSame('paid', PaymentStatus::derive(0, 0));
    }

    public function test_no_payments_against_a_positive_balance_is_unpaid(): void
    {
        $this->assertSame('unpaid', PaymentStatus::derive(0, 100));
    }

    public function test_partial_payment_is_partial(): void
    {
        $this->assertSame('partial', PaymentStatus::derive(40, 100));
    }

    public function test_full_payment_is_paid(): void
    {
        $this->assertSame('paid', PaymentStatus::derive(100, 100));
    }

    public function test_overpayment_is_still_paid(): void
    {
        $this->assertSame('paid', PaymentStatus::derive(120, 100));
    }
}
