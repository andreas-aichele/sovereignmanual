<?php

namespace App\Filament\Resources\ContentTopics\Pages;

use App\Filament\Resources\ContentTopics\ContentTopicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContentTopics extends ListRecords
{
    protected static string $resource = ContentTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
