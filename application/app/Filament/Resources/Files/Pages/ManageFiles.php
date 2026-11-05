<?php

declare(strict_types=1);

namespace App\Filament\Resources\Files\Pages;

use App\Actions\ResizeImageAction;
use App\Filament\Resources\Files\FileResource;
use App\Jobs\ConvertPdfToImages;
use App\Models\File;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageFiles extends ManageRecords
{
    protected static string $resource = FileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadImages')
                ->label('Upload Files')
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    FileUpload::make('files')
                        ->multiple()
                        ->previewable()
                        ->disk('public')
                        ->directory('uploads')
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $skippedCount = 0;
                    $pdfCount = 0;
                    foreach ($data['files'] as $path) {
                        $hash = md5(Storage::disk('public')->get($path));

                        if (File::where('hash', $hash)->exists()) {
                            Storage::disk('public')->delete($path);
                            $skippedCount++;

                            continue;
                        }

                        $mimeType = Storage::disk('public')->mimeType($path);

                        // The hash stays that of the original upload, so re-uploading it is still detected.
                        if (str_starts_with($mimeType, 'image/')) {
                            ResizeImageAction::execute(Storage::disk('public')->path($path), File::MAX_IMAGE_WIDTH);
                        }

                        $file = File::create([
                            'filename' => basename($path),
                            'path' => $path,
                            'mime_type' => $mimeType,
                            'size' => Storage::disk('public')->size($path),
                            'hash' => $hash,
                        ]);

                        if ($file->isPdf()) {
                            ConvertPdfToImages::dispatch($file);
                            $pdfCount++;
                        }
                    }

                    if ($pdfCount > 0) {
                        Notification::make()
                            ->info()
                            ->title('Converting PDFs')
                            ->body("{$pdfCount} PDF(s) are being converted to images. Their pages will appear in the list shortly.")
                            ->send();
                    }

                    if ($skippedCount > 0) {
                        Notification::make()
                            ->warning()
                            ->title('Some files were already uploaded')
                            ->body("{$skippedCount} duplicate files were skipped and deleted.")
                            ->send();
                    }
                })
                ->successNotificationTitle('Files uploaded successfully'),
            CreateAction::make(),
        ];
    }
}
