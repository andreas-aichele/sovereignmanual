<?php

namespace App\Filament\Resources\Audits;

use App\Filament\Resources\Audits\Pages\ListAudits;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use OwenIt\Auditing\Models\Audit;
use UnitEnum;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Automation';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event')->badge()->sortable(),
                TextColumn::make('auditable_type')->label('Model')->searchable()->toggleable(),
                TextColumn::make('auditable_id')->label('Record')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('url')->limit(40)->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAudits::route('/'),
        ];
    }
}
