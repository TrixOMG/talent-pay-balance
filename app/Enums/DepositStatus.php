<?php

namespace App\Enums;

enum DepositStatus: string
{
    case PENDING = 'pending';
    case REJECTED = 'rejected';
    case CONFIRMED = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::REJECTED => 'Отклонен',
            self::CONFIRMED => 'Подтвержден',
            self::PENDING => 'Ожидает подтверждения',
        };
    }
}
