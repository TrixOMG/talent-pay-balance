<?php

namespace App\Filament\Admin\Resources;

use Exception;
use BackedEnum;
use App\Enums\DepositStatus;
use App\Filament\Admin\Resources\DepositRequestResource\Pages;
use App\Models\DepositRequest;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DepositRequestResource extends Resource
{
    protected static ?string $model = DepositRequest::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Запросы на пополнение';
    protected static ?string $modelLabel = 'Запрос на пополнение';
    protected static ?string $pluralModelLabel = 'Запросы на пополнение';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Клиент')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn(DepositStatus $state): string => $state->label())
                    ->color(fn(DepositStatus $state): string => match ($state) {
                        DepositStatus::PENDING => 'warning',
                        DepositStatus::CONFIRMED => 'success',
                        DepositStatus::REJECTED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(DepositStatus::class),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(DepositRequest $record) => $record->status === DepositStatus::PENDING)
                    ->requiresConfirmation()
                    ->action(function (DepositRequest $record) {
                        $walletService = app(WalletService::class);
                        try {
                            $walletService->confirmDeposit($record, auth()->user());
                            Notification::make()
                                ->title('Пополнение подтверждено')
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
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepositRequests::route('/'),
        ];
    }
}
