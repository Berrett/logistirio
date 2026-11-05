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
            ->defaultSort('created_at', 'desc')
            // Refresh while uploaded PDFs are still being converted to images.
            ->poll(fn (): ?string => File::where('mime_type', 'application/pdf')->exists() ? '3s' : null)
            ->columns([
                ImageColumn::make('path')
                    ->label('Preview')
                    ->disk('public')
                    ->getStateUsing(fn (File $record): ?string => $record->isImage() ? $record->path : null)
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
                            ->visible($record->isImage())
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
                    ->visible(fn (File $record): bool => $record->isImage() && $record->analyses()->doesntExist())
                    ->action(function (File $record, OpenAiService $service) {
                        $aiRequest = $service->analyzeImage($record);
                        $response = $aiRequest->response;

                        if ($aiRequest->response_status !== 'completed') {
                            Notification::make()
                                ->danger()
                                ->title('Analysis failed')
                                ->body($aiRequest->errorMessage())
                                ->persistent()
                                ->send();

                            return;
                        }

                        // e.g. the second page of a multi-page PDF, which has no receipt header.
                        if (blank(data_get($response, 'date')) || ! is_array(data_get($response, 'products'))) {
                            Notification::make()
                                ->warning()
                                ->title('No receipt data found')
                                ->body('The image does not contain a receipt date and products, so no analysis was created.')
                                ->send();

                            return;
                        }

                        CreateAnalysisAction::execute($record, $aiRequest);

                        Notification::make()
                            ->success()
                            ->title('Analysis completed')
                            ->body('The image has been analyzed successfully.')
                            ->send();
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
