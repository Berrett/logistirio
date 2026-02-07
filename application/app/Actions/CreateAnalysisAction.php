<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AiRequest;
use App\Models\Analysis;
use App\Models\File;
use Carbon\Carbon;

class CreateAnalysisAction
{
    public static function execute(File $file, AiRequest $aiRequest)
    {

        $response = $aiRequest->response;
        $totalAmount = data_get($response, 'total_amount', 0);
        foreach ($response['products'] as $product) {
            $tax = data_get($product, 'tax_number', 0);
            $tax = intval(str_replace('%', '', $tax));
            $netPrice = data_get($product, 'net_price', 0);
            $grossPrice = data_get($product, 'gross_price', 0);
            $taxAmount = $grossPrice * ($tax / 100);

            if ($netPrice === $grossPrice) {
                $netPrice = $netPrice - $taxAmount;
            }

            $analysis = new Analysis;
            $analysis->fill([
                'file_id' => $file->id,
                'ai_request_id' => $aiRequest->id,
                'identifier' => data_get($response, 'receipt_number'),
                'date' => Carbon::createFromFormat('d/m/Y', data_get($response, 'date')),
                'total_amount' => $totalAmount,
                'tax' => $tax,
                'tax_amount' => $taxAmount,
                'net_price' => $netPrice,
                'gross_price' => $grossPrice,
            ]);

            $analysis->save();
        }
    }
}
