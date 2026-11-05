<?php

declare(strict_types=1);

use App\Filament\Resources\Files\Pages\ManageFiles;
use App\Models\AiRequest;
use App\Models\File;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    config()->set('services.openai.api_key', 'test-key');

    Storage::disk('public')->put('uploads/receipt.jpg', UploadedFile::fake()->image('receipt.jpg', 100, 100)->getContent());
    $this->file = File::create([
        'filename' => 'receipt.jpg',
        'path' => 'uploads/receipt.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 100,
        'hash' => 'abc',
    ]);
});

test('an openai error is logged and shown in the notification', function () {
    $message = 'You have no credits remaining. Add credits to continue using the API.';
    Http::fake(['api.openai.com/*' => Http::response([
        'error' => ['message' => $message, 'type' => 'insufficient_quota', 'code' => 'credit_balance_exhausted'],
    ], 429)]);
    Log::spy();

    Livewire::test(ManageFiles::class)
        ->callAction(TestAction::make('analyze')->table($this->file))
        ->assertNotified(Notification::make()->danger()->title('Analysis failed')->body($message)->persistent());

    Log::shouldHaveReceived('warning')
        ->with('OpenAI receipt analysis failed', Mockery::on(fn (array $context) => $context['status'] === 429 && $context['error'] === $message))
        ->once();
    expect(AiRequest::sole()->response_status)->toBe('failed');
});

test('a connection error is logged and its message kept', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
    Log::spy();

    Livewire::test(ManageFiles::class)
        ->callAction(TestAction::make('analyze')->table($this->file))
        ->assertNotified(Notification::make()->danger()->title('Analysis failed')->body('cURL error 28: Operation timed out')->persistent());

    Log::shouldHaveReceived('error')->with('OpenAI receipt analysis request errored', Mockery::any())->once();
    expect(AiRequest::sole()->errorMessage())->toBe('cURL error 28: Operation timed out');
});
