<?php

namespace App\Filament\Resources\ContentTopics\Pages;

use App\Filament\Resources\ContentTopics\ContentTopicResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContentTopic extends EditRecord
{
    protected static string $resource = ContentTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
