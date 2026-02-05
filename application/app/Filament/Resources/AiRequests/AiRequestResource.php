<?php

namespace App\Filament\Resources\AiRequests;

use App\Filament\Resources\AiRequests\Pages\ManageAiRequests;
use App\Models\AiRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;

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
