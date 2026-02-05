<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Models\File;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
     * @param File $image
     * @return array|null
     */
    public function analyzeImage(File $image): ?array
    {
        $prompt = $this->getReceiptPrompt();

        $aiRequest = AiRequest::create([
            'prompt' => $prompt,
            'file_id' => $image->id,
            'response_status' => 'pending',
        ]);

        $path = Storage::disk('local')->path($image->path);
        $result = $this->analyzeReceipt($path, $prompt);

        $aiRequest->update($result);
        return $result;
    }

    /**
     * Get the default prompt for receipt analysis.
     *
     * @return string
     */
    protected function getReceiptPrompt(): string
    {
        return "Extract the following details from this receipt:
1. Receipt number (receipt_number)
2. Date (date)
3. For each product/item (products):
   - Net price (net_price)
   - Tax number/amount (tax_number)
   - Gross price (gross_price)

Return the data as a JSON object with these exact keys.";
    }

    /**
     * Analyze a receipt file and extract details.
     *
     * @param string $imagePath Path to the file file
     * @param string|null $prompt Optional prompt to use
     * @return array|null Extracted data or null on failure
     */
    public function analyzeReceipt(string $imagePath, ?string $prompt = null): ?array
    {
        $prompt = $prompt ?? $this->getReceiptPrompt();

        if (empty($this->apiKey)) {
            logger('OpenAI API key is not set in services.php config.');
            return null;
        }

        if (!file_exists($imagePath)) {
            logger("Receipt file file not found at: {$imagePath}");
            return null;
        }

        try {
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);

            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant that extracts data from receipt files into structured JSON format.'
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
                                        'url' => "data:{$mimeType};base64,{$imageData}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            $content = $response->json();
            if ($response->successful()) {
                $content = $content['choices'][0]['message']['content'] ?? '{}';
            }

            return [
                'response_code' => $response->status(),
                'response' => $content,
            ];

        } catch (Exception $e) {
            return [
                'response_code' => 500,
                'response' => $e->getMessage(),
            ];
        }
    }
}
