<?php

namespace App\Filament\Resources\PageContentResource\Pages;

use App\Filament\Resources\PageContentResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPageContents extends ListRecords
{
    protected static string $resource = PageContentResource::class;

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getEloquentQuery()->orderBy('created_at', 'desc');
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
