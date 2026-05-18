<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Списание средств</x-slot>

        {{ $this->form }}

        <x-filament::button wire:click="withdraw" style="margin-top: 16px;">
            Выполнить списание
        </x-filament::button>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Доступные для возврата списания</x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>