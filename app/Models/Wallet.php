<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'currency', 'balance', 'version'])]
class Wallet extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'version' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function depositRequests()
    {
        return $this->hasMany(DepositRequest::class);
    }

    public static function getOrCreate(User $user, string $currency = 'RUB'): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $user->id,
                'currency' => $currency,
            ],
            [
                'balance' => 0,
                'version' => 1,
            ]
        );
    }
}
