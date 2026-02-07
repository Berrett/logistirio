<?php

declare(strict_types=1);

namespace App\Filament\Resources\Analyses;

use App\Filament\Resources\Analyses\Pages\ListAnalyses;
use App\Filament\Resources\Analyses\Schemas\AnalysisInfolist;
use App\Filament\Resources\Analyses\Tables\AnalysesTable;
use App\Models\Analysis;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AnalysisResource extends Resource
{
    protected static ?string $model = Analysis::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $recordTitleAttribute = 'identifier';

    public static function infolist(Schema $schema): Schema
    {
        return AnalysisInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnalysesTable::configure($table);
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
            'index' => ListAnalyses::route('/'),
        ];
    }
}
