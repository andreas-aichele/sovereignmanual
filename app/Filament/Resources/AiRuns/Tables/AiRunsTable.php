<?php

namespace App\Filament\Resources\AiRuns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AiRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('post.topic')->searchable(),
                TextColumn::make('contentTopic.title')->searchable(),
                TextColumn::make('provider')->sortable(),
                TextColumn::make('model')->toggleable(),
                TextColumn::make('started_at')->dateTime()->sortable(),
                TextColumn::make('finished_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
