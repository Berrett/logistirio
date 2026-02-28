<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Analysis;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AnalysisExporter extends Exporter
{
    protected static ?string $model = Analysis::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('identifier')
                ->label('Receipt Number'),
            ExportColumn::make('date')
                ->label('Date'),
            ExportColumn::make('amount_6')
                ->label('Καθ.Τιμή 6%'),
            ExportColumn::make('vat_6')
                ->label('ΦΠΑ 6%'),
            ExportColumn::make('amount_13')
                ->label('Καθ.Τιμή 13%'),
            ExportColumn::make('vat_13')
                ->label('ΦΠΑ 13%'),
            ExportColumn::make('amount_24')
                ->label('Καθ.Τιμή 24%'),
            ExportColumn::make('vat_24')
                ->label('ΦΠΑ 24%'),
            ExportColumn::make('total_net_price')
                ->label('Σύνολο Καθ. Αξίας'),
            ExportColumn::make('tax_amount_price')
                ->label('Σύνολο ΦΠΑ'),
            ExportColumn::make('total_amount')
                ->label('ΣΥΝΟΛΟ'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your analysis report export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' were exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
