<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Analysis;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalysisReport extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = Analysis::query()
            ->select(
                DB::raw('MIN(id) as id'),
                'identifier',
                'date',
                DB::raw('SUM(CASE WHEN tax = 6 THEN net_price ELSE 0 END) as amount_6'),
                DB::raw('SUM(CASE WHEN tax = 6 THEN tax_amount ELSE 0 END) as vat_6'),
                DB::raw('SUM(CASE WHEN tax = 13 THEN net_price ELSE 0 END) as amount_13'),
                DB::raw('SUM(CASE WHEN tax = 13 THEN tax_amount ELSE 0 END) as vat_13'),
                DB::raw('SUM(CASE WHEN tax = 24 THEN net_price ELSE 0 END) as amount_24'),
                DB::raw('SUM(CASE WHEN tax = 24 THEN tax_amount ELSE 0 END) as vat_24'),
                DB::raw('SUM(net_price) as total_net_price'),
                DB::raw('SUM(tax_amount) as tax_amount_price'),
                DB::raw('SUM(gross_price) as total_amount')
            )
            ->groupBy('identifier', 'date');

        return $table
            ->query(Analysis::query()->fromSub($query, 'analysis'))
            ->defaultSort('date')
            ->columns([
                Tables\Columns\TextColumn::make('identifier')
                    ->label('Receipt Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_6')
                    ->label('Καθ.Τιμή 6%')
                    ->money('EUR')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('')->money('EUR')),
                Tables\Columns\TextColumn::make('vat_6')
                    ->label('ΦΠΑ 6%')
                    ->money('EUR')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('')->money('EUR')),
                Tables\Columns\TextColumn::make('amount_13')
                    ->label('Καθ.Τιμή 13%')
                    ->money('EUR')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('')->money('EUR')),
                Tables\Columns\TextColumn::make('vat_13')
                    ->label('ΦΠΑ 13%')
                    ->money('EUR')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('')->money('EUR')),
                Tables\Columns\TextColumn::make('amount_24')
                    ->label('Καθ.Τιμή 24%')
                    ->money('EUR')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('')->money('EUR')),
                Tables\Columns\TextColumn::make('vat_24')
                    ->label('ΦΠΑ 24%')
                    ->money('EUR')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('')->money('EUR')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('ΣΥΝΟΛΟ')
                    ->money('EUR')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('')->money('EUR')),
            ])
            ->paginated(false)
            ->filters([
                Tables\Filters\SelectFilter::make('month')
                    ->label('Μήνας')
                    ->default(date('m'))
                    ->options([
                        '01' => 'Ιανουάριος',
                        '02' => 'Φεβρουάριος',
                        '03' => 'Μάρτιος',
                        '04' => 'Απρίλιος',
                        '05' => 'Μάιος',
                        '06' => 'Ιούνιος',
                        '07' => 'Ιούλιος',
                        '08' => 'Αύγουστος',
                        '09' => 'Σεπτέμβριος',
                        '10' => 'Οκτώβριος',
                        '11' => 'Νοέμβριος',
                        '12' => 'Δεκέμβριος',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $date): Builder => $query->whereMonth('date', $date),
                        );
                    }),
                Tables\Filters\SelectFilter::make('year')
                    ->label('Έτος')
                    ->default(date('Y'))
                    ->selectablePlaceholder(false)
                    ->options(function () {
                        $firstYear = Analysis::query()->min('date');
                        $lastYear = Analysis::query()->max('date');

                        if (! $firstYear || ! $lastYear) {
                            return [date('Y') => date('Y')];
                        }

                        $firstYear = \Illuminate\Support\Carbon::parse($firstYear)->year;
                        $lastYear = \Illuminate\Support\Carbon::parse($lastYear)->year;

                        $years = range($lastYear, $firstYear);

                        return array_combine($years, $years);
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $date): Builder => $query->whereYear('date', $date),
                        );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2);
    }
}
