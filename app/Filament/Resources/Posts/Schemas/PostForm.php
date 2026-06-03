<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Publishing')
                    ->components([
                        TextInput::make('topic')->required()->maxLength(255),
                        TextInput::make('slug')->required()->maxLength(255),
                        Select::make('status')
                            ->options(self::postStatusOptions())
                            ->required(),
                        Select::make('audience_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->required(),
                        TextInput::make('primary_language')->required()->maxLength(5),
                        DateTimePicker::make('scheduled_for'),
                        DateTimePicker::make('published_at'),
                    ])
                    ->columns(2),
                Section::make('Translations')
                    ->components([
                        Repeater::make('translations')
                            ->relationship()
                            ->schema([
                                TextInput::make('locale')->required()->maxLength(5),
                                TextInput::make('title')->required()->maxLength(255),
                                TextInput::make('slug')->required()->maxLength(255),
                                TextInput::make('meta_title')->maxLength(255),
                                TextInput::make('meta_description')->maxLength(255),
                                MarkdownEditor::make('markdown')->required()->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('AI')
                    ->components([
                        KeyValue::make('seo')->columnSpanFull(),
                        KeyValue::make('ai_metadata')->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function postStatusOptions(): array
    {
        return collect(PostStatus::cases())
            ->mapWithKeys(fn (PostStatus $status): array => [$status->value => str($status->value)->replace('_', ' ')->title()->toString()])
            ->all();
    }
}
