<?php

declare(strict_types=1);

namespace App\Filament\Resources\Analyses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class AnalysesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ai_request_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('identifier')
                    ->searchable(),
                TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                TextInputColumn::make('tax')
                    ->type('number')
                    ->sortable(),
                TextInputColumn::make('tax_amount')
                    ->prefix('EUR ')
                    ->type('number')
                    ->step('0.01')
                    ->sortable(),
                TextInputColumn::make('net_price')
                    ->prefix('EUR ')
                    ->type('number')
                    ->step('0.01')
                    ->sortable(),
                TextInputColumn::make('gross_price')
                    ->prefix('EUR ')
                    ->type('number')
                    ->step('0.01')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
