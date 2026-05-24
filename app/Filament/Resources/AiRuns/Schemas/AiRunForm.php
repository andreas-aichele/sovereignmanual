<?php

namespace App\Filament\Resources\AiRuns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiRunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Run')
                    ->components([
                        Select::make('post_id')->relationship('post', 'topic')->searchable(),
                        Select::make('content_topic_id')->relationship('contentTopic', 'title')->searchable(),
                        TextInput::make('type')->required(),
                        TextInput::make('status')->required(),
                        TextInput::make('provider'),
                        TextInput::make('model'),
                        DateTimePicker::make('started_at'),
                        DateTimePicker::make('finished_at'),
                        Textarea::make('prompt')->columnSpanFull(),
                        Textarea::make('response')->columnSpanFull(),
                        Textarea::make('error')->columnSpanFull(),
                        KeyValue::make('input')->columnSpanFull(),
                        KeyValue::make('output')->columnSpanFull(),
                        KeyValue::make('metrics')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
