<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\DepositRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WalletService
{
    public function createDepositRequest(Wallet $wallet, float $amount, ?string $comment = null): DepositRequest
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Сумма пополнения должна быть положительной');
        }

        return DepositRequest::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'amount' => $amount,
            'comment' => $comment,
            'status' => DepositStatus::PENDING,
        ]);
    }

    public function confirmDeposit(DepositRequest $depositRequest, User $admin): Transaction
    {
        if ($depositRequest->status !== DepositStatus::PENDING) {
            throw new InvalidArgumentException(
                "Запрос на пополнение #{$depositRequest->id} уже имеет статус {$depositRequest->status}"
            );
        }

        if (!$admin->isAdmin()) {
            throw new InvalidArgumentException('Только администратор может подтверждать пополнения');
        }

        return DB::transaction(function () use ($depositRequest, $admin) {
            $wallet = Wallet::where('id', $depositRequest->wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $depositRequest->amount;

            $updated = Wallet::where('id', $wallet->id)
                ->where('version', $wallet->version)
                ->update([
                    'balance' => $balanceAfter,
                    'version' => $wallet->version + 1,
                ]);

            if (!$updated) {
                throw new RuntimeException('Обнаружена конкурентная модификация кошелька');
            }

            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'amount' => $depositRequest->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => TransactionStatus::COMPLETED,
                'description' => "Пополнение баланса" . ($depositRequest->comment ? ": {$depositRequest->comment}" : ""),
                'metadata' => [
                    'deposit_request_id' => $depositRequest->id,
                    'confirmed_by' => $admin->name,
                    'confirmed_at' => now()->toDateTimeString(),
                ],
            ]);

            $depositRequest->update([
                'status' => DepositStatus::CONFIRMED,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            return $transaction;
        });
    }

    public function withdraw(Wallet $wallet, float $amount, string $description, User $admin): Transaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Сумма списания должна быть положительной');
        }

        if (!$admin->isAdmin()) {
            throw new InvalidArgumentException('Только администратор может выполнять списания');
        }

        return DB::transaction(function () use ($wallet, $amount, $description, $admin) {
            $lockedWallet = Wallet::where('id', $wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWallet->balance < $amount) {
                throw new InvalidArgumentException(
                    "Недостаточно средств на балансе. Требуется: {$amount}, доступно: {$lockedWallet->balance}"
                );
            }

            $balanceBefore = $lockedWallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            $updated = Wallet::where('id', $lockedWallet->id)
                ->where('version', $lockedWallet->version)
                ->update([
                    'balance' => $balanceAfter,
                    'version' => $lockedWallet->version + 1,
                ]);

            if (!$updated) {
                throw new RuntimeException('Обнаружена конкурентная модификация кошелька');
            }

            return Transaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => TransactionType::WITHDRAWAL,
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => TransactionStatus::COMPLETED,
                'description' => $description,
                'metadata' => [
                    'performed_by' => $admin->name,
                    'performed_at' => now()->toDateTimeString(),
                ],
            ]);
        });
    }

    public function refund(Transaction $originalTransaction, User $admin): Transaction
    {
        if ($originalTransaction->type !== TransactionType::WITHDRAWAL) {
            throw new InvalidArgumentException('Возврат возможен только для операций списания');
        }

        if ($originalTransaction->hasRefund()) {
            throw new InvalidArgumentException('По данной операции уже был выполнен возврат');
        }

        if (!$admin->isAdmin()) {
            throw new InvalidArgumentException('Только администратор может выполнять возвраты');
        }

        return DB::transaction(function () use ($originalTransaction, $admin) {
            $wallet = Wallet::where('id', $originalTransaction->wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $refundAmount = abs((float)$originalTransaction->amount);
            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $refundAmount;

            $updated = Wallet::where('id', $wallet->id)
                ->where('version', $wallet->version)
                ->update([
                    'balance' => $balanceAfter,
                    'version' => $wallet->version + 1,
                ]);

            if (!$updated) {
                throw new RuntimeException('Обнаружена конкурентная модификация кошелька');
            }

            return Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'refund',
                'amount' => $refundAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => TransactionStatus::COMPLETED,
                'description' => "Возврат по операции: {$originalTransaction->description}",
                'parent_transaction_id' => $originalTransaction->id,
                'metadata' => [
                    'original_transaction_id' => $originalTransaction->id,
                    'refunded_by' => $admin->name,
                    'refunded_at' => now()->toDateTimeString(),
                    'original_amount' => abs((float)$originalTransaction->amount),
                    'original_description' => $originalTransaction->description,
                ],
            ]);
        });
    }

    public function getTransactionHistory(Wallet $wallet, int $perPage = 20)
    {
        return $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
