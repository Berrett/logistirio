<?php

namespace App\Filament\Resources\Analyses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AnalysisInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('file_id')
                    ->numeric(),
                TextEntry::make('ai_request_id')
                    ->numeric(),
                TextEntry::make('identifier'),
                TextEntry::make('date')
                    ->dateTime(),
                TextEntry::make('tax')
                    ->numeric(),
                TextEntry::make('net_price')
                    ->money(),
                TextEntry::make('gross_price')
                    ->money(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
