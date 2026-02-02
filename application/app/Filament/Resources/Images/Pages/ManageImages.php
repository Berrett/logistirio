<?php

namespace App\Filament\Resources\Images\Pages;

use App\Filament\Resources\Images\ImageResource;
use App\Models\Image;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageImages extends ManageRecords
{
    protected static string $resource = ImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadImages')
                ->label('Upload Images')
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    FileUpload::make('images')
                        ->multiple()
                        ->directory('uploads')
                        ->disk('public')
                        ->required(),
                ])
                ->action(function (array $data) {
                    foreach ($data['images'] as $imagePath) {
                        Image::create([
                            'filename' => basename($imagePath),
                            'path' => $imagePath,
                            'mime_type' => Storage::disk('public')->mimeType($imagePath),
                            'size' => Storage::disk('public')->size($imagePath),
                        ]);
                    }
                })
                ->successNotificationTitle('Images uploaded successfully'),
            CreateAction::make(),
        ];
    }
}
