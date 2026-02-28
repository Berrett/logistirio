<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiRequests;

use App\Actions\CreateAnalysisAction;
use App\Filament\Resources\AiRequests\Pages\ManageAiRequests;
use App\Models\AiRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AiRequestResource extends Resource
{
    protected static ?string $model = AiRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('file_id')
                    ->relationship('file', 'filename')
                    ->required(),
                TextInput::make('response_status'),
                Textarea::make('prompt')
                    ->columnSpanFull(),
                Textarea::make('response')
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file.filename')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('prompt')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('response_status')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('analyze')
                    ->label('Analyze Response')
                    ->icon('heroicon-o-cpu-chip')
                    ->visible(fn (AiRequest $record): bool => $record->response_status === 'completed' && $record->analyses()->doesntExist())
                    ->action(function (AiRequest $record) {
                        CreateAnalysisAction::execute($record->file, $record);

                        Notification::make()
                            ->success()
                            ->title('Analysis completed')
                            ->body('The AI response has been analyzed successfully.')
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
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
            'index' => ManageAiRequests::route('/'),
        ];
    }
}
