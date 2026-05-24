<?php

namespace App\Filament\Resources\ContentTopics\Tables;

use App\Enums\ContentTopicStatus;
use App\Jobs\GeneratePostFromTopic;
use App\Models\ContentTopic;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContentTopicsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('priority')->sortable(),
                TextColumn::make('scheduled_for')->dateTime()->sortable(),
                TextColumn::make('last_generated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(ContentTopicStatus::cases())
                        ->mapWithKeys(fn (ContentTopicStatus $status): array => [$status->value => str($status->value)->replace('_', ' ')->title()->toString()])
                        ->all()),
            ])
            ->recordActions([
                Action::make('generate')
                    ->requiresConfirmation()
                    ->action(fn (ContentTopic $record) => GeneratePostFromTopic::dispatch($record)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
