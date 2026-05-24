<?php

namespace App\Filament\Resources\PostAssets\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asset')
                    ->components([
                        Select::make('post_id')->relationship('post', 'topic')->searchable(),
                        Select::make('type')
                            ->options(['image' => 'Image'])
                            ->required(),
                        TextInput::make('disk')->required()->maxLength(255),
                        TextInput::make('path')->maxLength(255),
                        TextInput::make('url')->url()->maxLength(255),
                        TextInput::make('locale')->maxLength(5),
                        TextInput::make('provider')->maxLength(255),
                        TextInput::make('model')->maxLength(255),
                        TextInput::make('status')->required()->maxLength(255),
                        TextInput::make('alt_text')->maxLength(255),
                        Textarea::make('prompt')->columnSpanFull(),
                        KeyValue::make('metadata')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
