<?php

namespace App\Filament\Resources\PostAssets;

use App\Filament\Resources\PostAssets\Pages\CreatePostAsset;
use App\Filament\Resources\PostAssets\Pages\EditPostAsset;
use App\Filament\Resources\PostAssets\Pages\ListPostAssets;
use App\Filament\Resources\PostAssets\Schemas\PostAssetForm;
use App\Filament\Resources\PostAssets\Tables\PostAssetsTable;
use App\Models\PostAsset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PostAssetResource extends Resource
{
    protected static ?string $model = PostAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Assets';

    protected static string|UnitEnum|null $navigationGroup = 'Blog CMS';

    public static function form(Schema $schema): Schema
    {
        return PostAssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostAssetsTable::configure($table);
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
            'index' => ListPostAssets::route('/'),
            'create' => CreatePostAsset::route('/create'),
            'edit' => EditPostAsset::route('/{record}/edit'),
        ];
    }
}
