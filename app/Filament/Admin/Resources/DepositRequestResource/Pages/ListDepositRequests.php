<?php

namespace App\Filament\Admin\Resources\DepositRequestResource\Pages;

use App\Filament\Admin\Resources\DepositRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListDepositRequests extends ListRecords
{
    protected static ?string $title = 'Запросы на пополнение';
    protected static string $resource = DepositRequestResource::class;


    public function getBreadcrumbs(): array
    {
        return  [];
    }
}
