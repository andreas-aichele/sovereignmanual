<?php

namespace App\Filament\Resources\PostAssets\Pages;

use App\Filament\Resources\PostAssets\PostAssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostAssets extends ListRecords
{
    protected static string $resource = PostAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
