<?php

namespace App\Filament\Resources\PostAssets\Pages;

use App\Filament\Resources\PostAssets\PostAssetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostAsset extends EditRecord
{
    protected static string $resource = PostAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
