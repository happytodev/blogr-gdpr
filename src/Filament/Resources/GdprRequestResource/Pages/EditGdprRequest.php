<?php

namespace Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource;

class EditGdprRequest extends EditRecord
{
    protected static string $resource = GdprRequestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === 'completed' && $this->record->completed_at === null) {
            $data['completed_at'] = now();
        }

        return $data;
    }
}
