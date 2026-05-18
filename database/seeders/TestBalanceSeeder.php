<?php

namespace Database\Seeders;

use App\Enums\DepositStatus;
use App\Enums\TransactionStatus;
use App\Models\DepositRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestBalanceSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::create([
            'name' => 'Иван Клиентов',
            'email' => 'client@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'client',
            'remember_token' => Str::random(10),
        ]);

        $wallet = Wallet::create([
            'user_id' => $client->id,
            'currency' => 'USD',
            'balance' => 1000.00,
            'version' => 1,
        ]);

        $admin = User::create([
            'name' => 'Админ Админов',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'remember_token' => Str::random(10),
        ]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => 1000.00,
            'balance_before' => 0,
            'balance_after' => 1000.00,
            'status' => TransactionStatus::COMPLETED,
            'description' => 'Начальное пополнение счета',
            'metadata' => [
                'confirmed_by' => $admin->name,
                'confirmed_at' => now()->subDays(7)->toDateTimeString(),
            ],
            'created_at' => now()->subDays(7),
        ]);

        $withdrawalTransaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'withdrawal',
            'amount' => -500.00,
            'balance_before' => 1000.00,
            'balance_after' => 500.00,
            'status' => TransactionStatus::COMPLETED,
            'description' => 'Оплата за проект: Разработка лендинга',
            'metadata' => [
                'performed_by' => $admin->name,
                'performed_at' => now()->subDays(2)->toDateTimeString(),
            ],
            'created_at' => now()->subDays(2),
        ]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'refund',
            'amount' => 500.00,
            'balance_before' => 500.00,
            'balance_after' => 1000.00,
            'status' => TransactionStatus::COMPLETED,
            'description' => 'Возврат по операции: Оплата за проект: Разработка лендинга',
            'parent_transaction_id' => $withdrawalTransaction->id,
            'metadata' => [
                'original_transaction_id' => $withdrawalTransaction->id,
                'refunded_by' => $admin->name,
                'refunded_at' => now()->subDay()->toDateTimeString(),
                'original_amount' => 500.00,
                'original_description' => 'Оплата за проект: Разработка лендинга',
            ],
            'created_at' => now()->subDay(),
        ]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'withdrawal',
            'amount' => -200.00,
            'balance_before' => 1000.00,
            'balance_after' => 800.00,
            'status' => TransactionStatus::COMPLETED,
            'description' => 'Оплата за дизайн-макеты',
            'metadata' => [
                'performed_by' => $admin->name,
                'performed_at' => now()->subHours(12)->toDateTimeString(),
            ],
            'created_at' => now()->subHours(12),
        ]);

        DepositRequest::create([
            'wallet_id' => $wallet->id,
            'user_id' => $client->id,
            'amount' => 500.00,
            'status' => DepositStatus::PENDING,
            'comment' => 'Пополнение для оплаты нового проекта',
            'created_at' => now()->subHours(1),
        ]);

        DepositRequest::create([
            'wallet_id' => $wallet->id,
            'user_id' => $client->id,
            'amount' => 1000.00,
            'status' => 'pending',
            'comment' => 'Пополнение на крупный проект',
            'created_at' => now()->subMinutes(30),
        ]);

        $currentBalance = 800.00; // 1000 - 500 + 500 - 200 = 800
        $wallet->update([
            'balance' => $currentBalance,
            'version' => $wallet->version + 4,
        ]);

        echo "\nТестовые данные созданы:\n";
        echo "---\n";
        echo "Клиент: client@example.com / password\n";
        echo "Админ: admin@example.com / password\n";
        echo "---\n";
        echo "Тестовые сценарии:\n";
        echo "- История пополнений\n";
        echo "- История списаний\n";
        echo "- Демонстрация возврата\n";
        echo "- Активные запросы на пополнение\n";
        echo "---\n";
        echo "Текущий баланс: {$currentBalance} RUB\n";
    }
}
