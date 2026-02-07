<?php

declare(strict_types=1);

namespace App\Filament\Resources\Files\Pages;

use App\Filament\Resources\Files\FileResource;
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
                        ->required(),
                ])
                ->action(function (array $data) {
                    $skippedCount = 0;
                    foreach ($data['files'] as $imagePath) {
                        $hash = md5(Storage::disk('public')->get($imagePath));

                        if (File::where('hash', $hash)->exists()) {
                            Storage::disk('public')->delete($imagePath);
                            $skippedCount++;

                            continue;
                        }

                        File::create([
                            'filename' => basename($imagePath),
                            'path' => $imagePath,
                            'mime_type' => Storage::disk('public')->mimeType($imagePath),
                            'size' => Storage::disk('public')->size($imagePath),
                            'hash' => $hash,
                        ]);
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
