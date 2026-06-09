<?php

namespace Happytodev\BlogrGdpr\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Happytodev\BlogrGdpr\Models\ConsentLog;

class ConsentLogResource extends Resource
{
    protected static ?string $model = ConsentLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'GDPR';

    protected static ?string $recordTitleAttribute = 'email';

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
                TextColumn::make('consent_given')
                    ->label('Consent')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
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
            ->columns(2)
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->disabled(),
                Select::make('consent_type')
                    ->label('Consent Type')
                    ->options([
                        'cookies' => 'Cookies',
                        'analytics' => 'Analytics',
                        'contact' => 'Contact',
                    ])
                    ->disabled(),
                Select::make('consent_given')
                    ->label('Consent Given')
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ])
                    ->disabled(),
                TextInput::make('ip_address')
                    ->label('IP Address')
                    ->disabled(),
                TextInput::make('user_agent')
                    ->label('User Agent')
                    ->disabled()
                    ->columnSpanFull(),
                TextInput::make('created_at')
                    ->label('Date')
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
