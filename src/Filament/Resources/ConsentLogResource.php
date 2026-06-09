<?php

namespace Happytodev\BlogrGdpr\Filament\Resources;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Happytodev\Blogr\Services\ExtensionRegistry;
use Happytodev\BlogrGdpr\Filament\Resources\ConsentLogResource\Pages\ListConsentLogs;
use Happytodev\BlogrGdpr\Filament\Resources\ConsentLogResource\Pages\ViewConsentLog;
use Happytodev\BlogrGdpr\Models\ConsentLog;

class ConsentLogResource extends Resource
{
    protected static ?string $model = ConsentLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'GDPR';

    protected static ?string $recordTitleAttribute = 'email';

    public static function shouldRegisterNavigation(): bool
    {
        if (! app()->has(ExtensionRegistry::class)) {
            return true;
        }

        return app(ExtensionRegistry::class)->isEnabled('blogr-gdpr');
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canView($record): bool
    {
        return auth()->check();
    }

    public static function getModelLabel(): string
    {
        return 'Consent Log';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Consent Logs';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('consent_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cookies' => 'warning',
                        'analytics' => 'info',
                        'contact' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('consent_data')
                    ->label('Categories')
                    ->formatStateUsing(fn ($state): string => match (true) {
                        is_array($state) && isset($state['categories']) => collect($state['categories'])->filter()->keys()->implode(', '),
                        is_array($state) => collect($state)->filter()->keys()->implode(', '),
                        is_string($state) => $state,
                        default => '',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('consent_given')
                    ->label('Consent')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state): string => $state ? 'Yes' : 'No'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(40),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('consent_type')
                    ->options([
                        'cookies' => 'Cookies',
                        'analytics' => 'Analytics',
                        'contact' => 'Contact',
                    ]),
                TernaryFilter::make('consent_given')
                    ->label('Consent Given')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->nullable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ConsentLog $record): string => ConsentLogResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Visitor Information')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->columnSpanFull(),
                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->icon('heroicon-m-computer-desktop'),
                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull()
                            ->limit(200),
                    ]),
                Section::make('Consent Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('consent_type')
                            ->label('Consent Type')
                            ->icon('heroicon-m-shield-check')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'cookies' => 'warning',
                                'analytics' => 'info',
                                'contact' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('consent_given')
                            ->label('Consent Given')
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn ($state): string => $state ? 'Yes' : 'No')
                            ->icon(fn ($state): string => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime()
                            ->icon('heroicon-m-calendar'),
                        TextEntry::make('consent_data')
                            ->label('Accepted Categories')
                            ->icon('heroicon-m-cube')
                            ->formatStateUsing(fn ($state): string => match (true) {
                                is_array($state) && isset($state['categories']) => collect($state['categories'])->filter()->keys()->implode(', '),
                                is_array($state) => collect($state)->filter()->keys()->implode(', '),
                                is_string($state) => $state,
                                default => '-',
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsentLogs::route('/'),
            'view' => ViewConsentLog::route('/{record}'),
        ];
    }
}
