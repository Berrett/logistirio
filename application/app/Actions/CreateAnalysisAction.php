<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AiRequest;
use App\Models\Analysis;
use App\Models\File;
use App\Services\HelperService;
use Carbon\Carbon;

class CreateAnalysisAction
{
    public static function execute(File $file, AiRequest $aiRequest)
    {
        $response = $aiRequest->response;
        $totalAmount = data_get($response, 'total_amount', 0);
        foreach ($response['products'] as $product) {
            $tax = (string) data_get($product, 'tax_number', 0);
            $tax = intval(str_replace('%', '', $tax));
            $grossPrice = (string) data_get($product, 'gross_price', 0);
            $grossPrice = floatval(str_replace(',', '.', $grossPrice));
            $taxAmount = $grossPrice - ($grossPrice / (($tax + 100) / 100));
            $netPrice = $grossPrice - $taxAmount;

            $analysis = new Analysis;
            $analysis->fill([
                'file_id' => $file->id,
                'ai_request_id' => $aiRequest->id,
                'identifier' => data_get($response, 'receipt_number'),
                'date' => Carbon::createFromFormat('d/m/Y', data_get($response, 'date')),
                'total_amount' => HelperService::formatCurrency($totalAmount),
                'tax' => $tax,
                'tax_amount' => $taxAmount,
                'net_price' => $netPrice,
                'gross_price' => $grossPrice,
            ]);

            $analysis->save();
        }
    }
}
