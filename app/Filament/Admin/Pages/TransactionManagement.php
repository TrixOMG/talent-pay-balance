<?php

namespace App\Filament\Admin\Pages;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Exception;
use BackedEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class TransactionManagement extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected string $view = 'filament.admin.pages.transaction-management';
    protected static ?string $title = 'Списания и возвраты';
    protected static ?string $navigationLabel = 'Управление транзакциями';

    public ?int $withdrawal_user_id;
    public ?float $withdrawal_amount;
    public ?string $withdrawal_description;

    protected function getFormSchema(): array
    {
        return [
            Select::make('withdrawal_user_id')
                ->label('Клиент')
                ->options(User::where('role', 'client')->pluck('name', 'id'))
                ->required()
                ->searchable(),
            TextInput::make('withdrawal_amount')
                ->label('Сумма списания')
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->prefix('RUB'),
            Textarea::make('withdrawal_description')
                ->label('Описание')
                ->required()
                ->maxLength(255),
        ];
    }

    public function withdraw()
    {
        $this->validate([
            'withdrawal_user_id' => 'required|exists:users,id',
            'withdrawal_amount' => 'required|numeric|min:0.01',
            'withdrawal_description' => 'required|string|max:255',
        ]);

        $wallet = Wallet::where('user_id', $this->withdrawal_user_id)->firstOrFail();
        $walletService = app(WalletService::class);

        try {
            $walletService->withdraw($wallet, $this->withdrawal_amount, $this->withdrawal_description, auth()->user());

            Notification::make()
                ->title('Списание выполнено')
                ->body("Сумма: {$this->withdrawal_amount} RUB. Описание: {$this->withdrawal_description}")
                ->success()
                ->send();

            $this->withdrawal_user_id = null;
            $this->withdrawal_amount = null;
            $this->withdrawal_description = null;
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
        return Transaction::where('type', TransactionType::WITHDRAWAL)
            ->whereDoesntHave(
                'refundTransactions',
                fn($query) => $query->where('status', TransactionStatus::COMPLETED)
            )
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable(),
            TextColumn::make('wallet.user.name')
                ->label('Клиент'),
            TextColumn::make('amount')
                ->label('Сумма списания')
                ->formatStateUsing(fn($state) => number_format(abs($state), 2) . ' RUB'),
            TextColumn::make('description')
                ->label('Описание'),
            TextColumn::make('created_at')
                ->label('Дата')
                ->dateTime(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('refund')
                ->label('Возврат')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (Transaction $record) {
                    $walletService = app(WalletService::class);
                    try {
                        $walletService->refund($record, auth()->user());
                        Notification::make()
                            ->title('Возврат выполнен')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Ошибка')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
