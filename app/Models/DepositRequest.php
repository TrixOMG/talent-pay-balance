<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

use function in_array;

#[Fillable([
    'wallet_id',
    'user_id',
    'amount',
    'status',
    'comment',
    'processed_by',
    'processed_at',
])]
class DepositRequest extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
            'status' => DepositStatus::class,
        ];
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === DepositStatus::PENDING;
    }

    public function isProcessed(): bool
    {
        return in_array($this->status, [DepositStatus::CONFIRMED, DepositStatus::REJECTED]);
    }
}
