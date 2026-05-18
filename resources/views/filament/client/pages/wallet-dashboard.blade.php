<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Текущий баланс</x-slot>

        <x-filament::fieldset>
            <x-slot name="label">Баланс кошелька</x-slot>
            <div style="font-size: 1.875rem; font-weight: bold; color: var(--primary-600);">
                {{ number_format($wallet->balance, 2) }} {{ $wallet->currency }}
            </div>
        </x-filament::fieldset>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Пополнить баланс</x-slot>

        {{ $this->form }}

        <x-filament::button wire:click="createDepositRequest" style="margin-top: 16px;">
            Создать запрос на пополнение
        </x-filament::button>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">История операций</x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>