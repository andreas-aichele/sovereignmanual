<?php

namespace App\Filament\Resources\ContentTopics;

use App\Filament\Resources\ContentTopics\Pages\CreateContentTopic;
use App\Filament\Resources\ContentTopics\Pages\EditContentTopic;
use App\Filament\Resources\ContentTopics\Pages\ListContentTopics;
use App\Filament\Resources\ContentTopics\Schemas\ContentTopicForm;
use App\Filament\Resources\ContentTopics\Tables\ContentTopicsTable;
use App\Models\ContentTopic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContentTopicResource extends Resource
{
    protected static ?string $model = ContentTopic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $navigationLabel = 'Topics';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return ContentTopicForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentTopicsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentTopics::route('/'),
            'create' => CreateContentTopic::route('/create'),
            'edit' => EditContentTopic::route('/{record}/edit'),
        ];
    }
}
