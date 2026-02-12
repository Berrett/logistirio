<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiRequest;
use App\Models\File;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OpenAiService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key') ?? '';
    }

    /**
     * Analyze an File model.
     *
     * @return array|null
     */
    public function analyzeImage(File $file): ?AiRequest
    {
        $prompt = $this->getReceiptPrompt();

        $aiRequest = AiRequest::create([
            'prompt' => $prompt,
            'file_id' => $file->id,
            'response_status' => 'pending',
        ]);

        $path = Storage::disk('public')->path($file->path);
        $result = $this->analyzeReceipt($path, $prompt);
        if ($result) {
            $response = json_decode(str_replace(['\n', '\\', '"{', '}"'], ['', '', '{', '}'], $result['response']), true);
            $aiRequest->update([
                'response' => $response,
                'response_status' => ($result['response_code'] ?? 0) == 200 ? 'completed' : 'failed',
            ]);
        } else {
            $aiRequest->update([
                'response_status' => 'failed',
                'response' => 'Analysis failed: Service returned no result.',
            ]);
        }

        return $aiRequest;
    }

    /**
     * Get the default prompt for receipt analysis.
     */
    protected function getReceiptPrompt(): string
    {
        return 'Extract the following details from this receipt:
1. Receipt number (receipt_number)
2. Date (date in format d/m/Y)
3. Total amount (total_amount)
4. For each product/item/service/participation etc (products):
   - Tax number/amount - the tax percentage without the symbol percentage (tax_number)
   - Gross price amount with vat (gross_price - total of each product)

Return the data as a JSON object with these exact keys and information.';
    }

    /**
     * Analyze a receipt file and extract details.
     *
     * @param  string  $filePath  Path to the file file
     * @param  string|null  $prompt  Optional prompt to use
     * @return array|null Extracted data or null on failure
     */
    public function analyzeReceipt(string $filePath, ?string $prompt = null): ?array
    {
        $prompt = $prompt ?? $this->getReceiptPrompt();

        if (empty($this->apiKey)) {
            logger('OpenAI API key is not set in services.php config.');

            return null;
        }

        if (! file_exists($filePath)) {
            logger("Receipt file file not found at: {$filePath}");

            return null;
        }
        $fileData = base64_encode(file_get_contents($filePath));
        $mimeType = mime_content_type($filePath);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant that extracts data from receipt files into structured JSON format.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$fileData}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'] ?? '{}';
            } else {
                $content = $response->body();
            }

            return [
                'response_code' => $response->status(),
                'response' => $content,
            ];

        } catch (Exception $e) {
            return [
                'response_code' => 422,
                'response' => $e->getMessage(),
            ];
        }
    }
}
