<?php

declare(strict_types=1);

namespace App\Filament\Resources\Files;

use App\Actions\CreateAnalysisAction;
use App\Filament\Resources\Files\Pages\ManageFiles;
use App\Models\File;
use App\Services\OpenAiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('Preview')
                    ->disk('public')
                    ->square(),
                TextColumn::make('path')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->searchable(),
                TextColumn::make('size')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('hash')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->schema(fn (File $record): array => [
                        Image::make($record->url, $record->filename)
                            ->visible(str_starts_with($record->mime_type ?? '', 'image/'))
                            ->imageHeight(500)
                            ->alignCenter(),
                        Text::make($record->filename),
                        Actions::make([
                            Action::make('openInNewTab')
                                ->label('Open in new tab')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->url($record->url)
                                ->openUrlInNewTab(),
                        ])->alignCenter(),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('analyze')
                    ->label('Analyze')
                    ->icon('heroicon-o-cpu-chip')
                    ->visible(fn (File $record): bool => $record->aiRequests()->doesntExist())
                    ->action(function (File $record, OpenAiService $service) {
                        $aiRequest = $service->analyzeImage($record);

                        CreateAnalysisAction::execute($record, $aiRequest);

                        if ($aiRequest && ($aiRequest->response_code ?? 0) == 200) {
                            Notification::make()
                                ->success()
                                ->title('Analysis completed')
                                ->body('The image has been analyzed successfully.')
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Analysis failed')
                                ->body($aiRequest->response ?? 'An error occurred during analysis.')
                                ->send();
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFiles::route('/'),
        ];
    }
}
