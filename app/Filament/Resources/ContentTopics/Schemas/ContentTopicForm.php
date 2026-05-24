<?php

namespace App\Filament\Resources\ContentTopics\Schemas;

use App\Enums\ContentTopicStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentTopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Topic')
                    ->components([
                        TextInput::make('title')->required()->maxLength(255),
                        TextInput::make('slug')->required()->maxLength(255),
                        TextInput::make('category')->required()->maxLength(255),
                        Select::make('status')
                            ->options(collect(ContentTopicStatus::cases())
                                ->mapWithKeys(fn (ContentTopicStatus $status): array => [$status->value => str($status->value)->replace('_', ' ')->title()->toString()])
                                ->all())
                            ->required(),
                        TextInput::make('priority')->numeric()->required(),
                        Select::make('audience_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->required(),
                        TextInput::make('primary_language')->required()->maxLength(5),
                        TagsInput::make('target_languages'),
                        DateTimePicker::make('scheduled_for'),
                        Textarea::make('brief')->columnSpanFull(),
                        KeyValue::make('constraints')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
