<?php

declare(strict_types=1);

use App\Actions\ResizeImageAction;
use App\Filament\Resources\Files\Pages\ManageFiles;
use App\Jobs\ConvertPdfToImages;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Fake pdftoppm: write one JPG per given page content using the output prefix (last argument).
 */
function fakePdftoppm(array $pageContents): void
{
    Process::fake(function (PendingProcess $process) use ($pageContents) {
        $prefix = end($process->command);

        foreach ($pageContents as $index => $content) {
            file_put_contents($prefix.'-'.($index + 1).'.jpg', $content);
        }

        return Process::result();
    });
}

function storePdf(): File
{
    Storage::disk('public')->put('uploads/receipt.pdf', '%PDF-1.4 fake');

    return File::create([
        'filename' => 'receipt.pdf',
        'path' => 'uploads/receipt.pdf',
        'mime_type' => 'application/pdf',
        'size' => 13,
        'hash' => md5('%PDF-1.4 fake'),
    ]);
}

test('uploading a pdf queues its conversion', function () {
    Queue::fake();

    Livewire::test(ManageFiles::class)
        ->callAction('uploadImages', data: [
            'files' => [UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')],
        ])
        ->assertHasNoActionErrors();

    $file = File::sole();
    expect($file->mime_type)->toBe('application/pdf');
    Queue::assertPushed(ConvertPdfToImages::class, fn ($job) => $job->file->is($file));
});

test('uploading an image resizes it and does not queue a conversion', function () {
    Queue::fake();
    $upload = UploadedFile::fake()->image('receipt.jpg', 1200, 1800);
    $originalHash = md5_file($upload->getRealPath());

    Livewire::test(ManageFiles::class)
        ->callAction('uploadImages', data: ['files' => [$upload]])
        ->assertHasNoActionErrors();

    $file = File::sole();
    [$width, $height] = getimagesize(Storage::disk('public')->path($file->path));
    expect($file->mime_type)->toBe('image/jpeg')
        ->and([$width, $height])->toBe([400, 600])
        ->and($file->size)->toBe(Storage::disk('public')->size($file->path))
        ->and($file->hash)->toBe($originalHash);
    Queue::assertNothingPushed();
});

test('images narrower than the max width are not upscaled', function () {
    Storage::disk('public')->put('small.png', UploadedFile::fake()->image('small.png', 300, 100)->getContent());
    $path = Storage::disk('public')->path('small.png');

    ResizeImageAction::execute($path, 400);

    expect(array_slice(getimagesize($path), 0, 2))->toBe([300, 100]);
});

test('exif orientation is applied before checking the width', function () {
    $path = Storage::disk('public')->path('rotated.jpg');
    $image = new Imagick;
    $image->newImage(600, 300, 'white');
    $image->setImageFormat('jpeg');

    // Imagick won't write an EXIF orientation for a new image, so insert an APP1 block with Orientation = 6 (rotate 90° CW).
    $tiff = "MM\x00\x2A\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00"."\x00\x00\x00\x00";
    $exif = "Exif\x00\x00".$tiff;
    $jpeg = $image->getImageBlob();
    file_put_contents($path, "\xFF\xD8\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2));
    expect((new Imagick($path))->getImageOrientation())->toBe(Imagick::ORIENTATION_RIGHTTOP);

    ResizeImageAction::execute($path, 400);

    $result = new Imagick($path);
    expect([$result->getImageWidth(), $result->getImageHeight()])->toBe([300, 600])
        ->and($result->getImageOrientation())->toBeLessThanOrEqual(Imagick::ORIENTATION_TOPLEFT);
});

test('each pdf page becomes a jpg file and the pdf is removed', function () {
    $pdf = storePdf();
    fakePdftoppm(['page one', 'page two']);

    ConvertPdfToImages::dispatchSync($pdf);

    Process::assertRan(fn (PendingProcess $process) => $process->command[0] === 'pdftoppm'
        && in_array('-scale-to-x', $process->command, true)
        && $process->command[array_search('-scale-to-x', $process->command, true) + 1] === '400');
    expect(File::where('mime_type', 'application/pdf')->exists())->toBeFalse();
    Storage::disk('public')->assertMissing('uploads/receipt.pdf');

    $pages = File::orderBy('id')->get();
    expect($pages)->toHaveCount(2)
        ->and($pages->pluck('path')->all())->toBe(['uploads/receipt-page-1.jpg', 'uploads/receipt-page-2.jpg'])
        ->and($pages->pluck('mime_type')->unique()->all())->toBe(['image/jpeg'])
        ->and($pages[0]->hash)->toBe(md5('page one'));
    Storage::disk('public')->assertExists('uploads/receipt-page-2.jpg');
});

test('pages that already exist are skipped', function () {
    File::create([
        'filename' => 'existing.jpg',
        'path' => 'uploads/existing.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 8,
        'hash' => md5('page one'),
    ]);
    $pdf = storePdf();
    fakePdftoppm(['page one', 'page two']);

    ConvertPdfToImages::dispatchSync($pdf);

    expect(File::pluck('path')->all())->toBe(['uploads/existing.jpg', 'uploads/receipt-page-2.jpg']);
});

test('a failed conversion keeps the pdf', function () {
    $pdf = storePdf();
    Process::fake(fn () => Process::result(errorOutput: 'Syntax Error', exitCode: 1));

    expect(fn () => ConvertPdfToImages::dispatchSync($pdf))->toThrow(Exception::class);

    expect($pdf->fresh())->not->toBeNull();
    Storage::disk('public')->assertExists('uploads/receipt.pdf');
});
