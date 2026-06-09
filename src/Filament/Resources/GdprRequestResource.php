<?php

namespace Happytodev\BlogrGdpr\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Happytodev\Blogr\Services\ExtensionRegistry;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages\EditGdprRequest;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages\ListGdprRequests;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages\ViewGdprRequest;
use Happytodev\BlogrGdpr\Models\GdprRequest;

class GdprRequestResource extends Resource
{
    protected static ?string $model = GdprRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'GDPR';

    protected static ?string $recordTitleAttribute = 'email';

    public static function shouldRegisterNavigation(): bool
    {
        if (! app()->has(ExtensionRegistry::class)) {
            return true;
        }

        return app(ExtensionRegistry::class)->isEnabled('blogr-gdpr');
    }

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
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (GdprRequest $record): string => GdprRequestResource::getUrl('edit', ['record' => $record])),
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (GdprRequest $record): string => GdprRequestResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('email')
                    ->disabled(),
                TextInput::make('request_type')
                    ->label('Request Type')
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ]),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('completed_at')
                    ->label('Completed At')
                    ->disabled(),
                TextInput::make('created_at')
                    ->label('Created At')
                    ->disabled(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Request Information')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->columnSpanFull(),
                        TextEntry::make('request_type')
                            ->label('Request Type')
                            ->icon('heroicon-m-arrow-up-tray')
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
                        TextEntry::make('status')
                            ->label('Status')
                            ->icon('heroicon-m-clock')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'completed' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->label('Submitted At')
                            ->dateTime()
                            ->icon('heroicon-m-calendar'),
                        TextEntry::make('notes')
                            ->label('Admin Notes')
                            ->columnSpanFull(),
                    ]),
                Section::make('Processing Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('completed_at')
                            ->label('Completed At')
                            ->dateTime()
                            ->icon('heroicon-m-check-badge')
                            ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGdprRequests::route('/'),
            'view' => ViewGdprRequest::route('/{record}'),
            'edit' => EditGdprRequest::route('/{record}/edit'),
        ];
    }
}
