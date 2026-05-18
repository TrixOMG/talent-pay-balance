<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'wallet_id',
    'type',
    'amount',
    'balance_before',
    'balance_after',
    'status',
    'description',
    'metadata',
    'parent_transaction_id',
])]
class Transaction extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function parentTransaction()
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    public function refundTransactions()
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }

    public function hasRefund(): bool
    {
        return $this->refundTransactions()
            ->where('status', TransactionStatus::COMPLETED)
            ->exists();
    }

    public function getAbsoluteAmountAttribute(): float
    {
        return abs((float)$this->amount);
    }

    public function isDeposit(): bool
    {
        return $this->amount > 0;
    }

    public function isWithdrawal(): bool
    {
        return $this->amount < 0;
    }
}
