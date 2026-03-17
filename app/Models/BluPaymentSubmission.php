<?php

namespace App\Models;

/**
 * BluPaymentSubmission is an alias for Transaction.
 *
 * Both models share the same `transactions` database table.
 * This alias exists so that route–model-binding resolves
 * to a descriptive class name without requiring a DB migration.
 */
class BluPaymentSubmission extends Transaction
{
    protected $table = 'transactions';
}
