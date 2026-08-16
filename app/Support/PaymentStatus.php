<?php

namespace App\Support;

class PaymentStatus
{
    /**
     * Derive an enrollment's payment_status from what's been paid against
     * what's actually owed (price minus any discount).
     *
     * An enrollment with nothing owed (e.g. fully discounted) is always
     * 'paid', regardless of amount_paid.
     */
    public static function derive(float $amountPaid, float $owed): string
    {
        if ($owed <= 0) {
            return 'paid';
        }
        if ($amountPaid <= 0) {
            return 'unpaid';
        }
        if ($amountPaid >= $owed) {
            return 'paid';
        }
        return 'partial';
    }
}
