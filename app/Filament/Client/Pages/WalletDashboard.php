<?php

namespace App\Filament\Client\Pages;

use App\Enums\TransactionType;
use Exception;
use BackedEnum;
use App\Models\Wallet;
use App\Services\WalletService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class WalletDashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedWallet;
    protected string $view = 'filament.client.pages.wallet-dashboard';

    public Wallet $wallet;
    public float $amount = 0;
    public string $comment = '';

    public function mount(): void
    {
        $this->wallet = auth()->user()->wallets()->firstOrFail();
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('amount')
                ->label('Сумма пополнения')
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->prefix('RUB'),
            Textarea::make('comment')
                ->label('Комментарий')
                ->maxLength(255),
        ];
    }

    public function createDepositRequest()
    {
        $this->validate([
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string|max:255',
        ]);

        $walletService = app(WalletService::class);

        try {
            $walletService->createDepositRequest($this->wallet, $this->amount, $this->comment);

            Notification::make()
                ->title('Запрос на пополнение создан')
                ->body("Сумма: {$this->amount} {$this->wallet->currency}. Ожидайте подтверждения администратором.")
                ->success()
                ->send();

            $this->amount = 0;
            $this->comment = '';
        } catch (Exception $e) {
            Notification::make()
                ->title('Ошибка')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getTableQuery()
    {
        return $this->wallet->transactions()->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label('Дата')
                ->dateTime('d.m.Y H:i')
                ->sortable(),
            TextColumn::make('type')
                ->label('Тип операции')
                ->formatStateUsing(fn(TransactionType $state): string => $state->label()),
            TextColumn::make('amount')
                ->label('Сумма')
                ->formatStateUsing(fn($state) => ($state > 0 ? '+' : '') . number_format($state, 2) . ' RUB')
                ->color(fn($state) => $state > 0 ? 'success' : 'danger'),
            TextColumn::make('description')
                ->label('Описание')
                ->searchable(),
            TextColumn::make('balance_after')
                ->label('Баланс после')
                ->formatStateUsing(fn($state) => number_format($state, 2) . ' RUB'),
        ];
    }
}
