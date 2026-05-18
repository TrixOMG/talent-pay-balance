<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Платеж выполнен',
        };
    }
}
