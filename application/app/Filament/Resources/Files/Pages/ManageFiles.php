<?php

namespace App\Filament\Resources\Files\Pages;

use App\Filament\Resources\Files\FileResource;
use App\Models\File;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
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
                        ->directory('uploads')
                        ->required(),
                ])
                ->action(function (array $data) {
                    foreach ($data['files'] as $imagePath) {
                        File::create([
                            'filename' => basename($imagePath),
                            'path' => $imagePath,
                            'mime_type' => Storage::mimeType($imagePath),
                            'size' => Storage::size($imagePath),
                        ]);
                    }
                })
                ->successNotificationTitle('Files uploaded successfully'),
            CreateAction::make(),
        ];
    }
}
