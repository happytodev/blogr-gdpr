<?php

namespace Happytodev\BlogrGdpr\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Happytodev\BlogrGdpr\Models\GdprRequest;

class GdprRequestResource extends Resource
{
    protected static ?string $model = GdprRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'GDPR';

    protected static ?string $recordTitleAttribute = 'email';

    public static function getModelLabel(): string
    {
        return 'GDPR Request';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Data Requests';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('request_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'export' => 'info',
                        'erasure' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'export' => 'Data Export',
                        'erasure' => 'Data Erasure',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('request_type')
                    ->options([
                        'export' => 'Data Export',
                        'erasure' => 'Data Erasure',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ]),
            ])
            ->recordActions([
                Action::make('markCompleted')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (GdprRequest $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (GdprRequest $record): void {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Request marked as completed')
                            ->success()
                            ->send();
                    }),
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (GdprRequest $record): string => GdprRequestResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->disabled(),
                Select::make('request_type')
                    ->label('Request Type')
                    ->options([
                        'export' => 'Data Export',
                        'erasure' => 'Data Erasure',
                    ])
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ])
                    ->disabled(),
                Textarea::make('notes')
                    ->disabled()
                    ->columnSpanFull(),
                TextInput::make('completed_at')
                    ->label('Completed At')
                    ->disabled(),
                TextInput::make('created_at')
                    ->label('Created At')
                    ->disabled(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecords::route('/'),
            'view' => ViewRecord::route('/{record}'),
        ];
    }
}
