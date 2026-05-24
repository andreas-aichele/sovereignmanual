<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Services\BlogAiPipeline;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('topic')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('audience_level')->badge(),
                TextColumn::make('review_score')->sortable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                TextColumn::make('next_review_at')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PostStatus::cases())
                        ->mapWithKeys(fn (PostStatus $status): array => [$status->value => str($status->value)->replace('_', ' ')->title()->toString()])
                        ->all()),
            ])
            ->recordActions([
                Action::make('publish')
                    ->requiresConfirmation()
                    ->visible(fn (Post $record): bool => $record->status !== PostStatus::Published)
                    ->action(fn (Post $record) => $record->update([
                        'status' => PostStatus::Published,
                        'published_at' => now(),
                    ])),
                Action::make('unpublish')
                    ->requiresConfirmation()
                    ->visible(fn (Post $record): bool => $record->status === PostStatus::Published)
                    ->action(fn (Post $record) => $record->update([
                        'status' => PostStatus::Draft,
                        'published_at' => null,
                    ])),
                Action::make('review freshness')
                    ->action(fn (Post $record, BlogAiPipeline $pipeline) => $pipeline->refreshPost($record)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
