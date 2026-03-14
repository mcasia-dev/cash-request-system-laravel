<?php

namespace App\Filament\Resources\ReplenishmentResource\Pages;

use App\Filament\Resources\ReplenishmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReplenishment extends EditRecord
{
    protected static string $resource = ReplenishmentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $initial = (float) ($data['initial_amount'] ?? 0);
        $total = collect($data['replenishmentItems'] ?? [])
            ->sum(fn($item) => (float) ($item['amount'] ?? 0));

        $data['total_amount'] = $total;
        $data['remaining_amount'] = max($initial - $total, 0);

        return $data;
    }

    protected function afterSave(): void
    {
        $total = (float) $this->record->replenishmentItems()->sum('amount');
        $initial = (float) $this->record->initial_amount;
        $remaining = max($initial - $total, 0);

        $this->record->update([
            'total_amount' => $total,
            'remaining_amount' => $remaining,
        ]);

        $this->record->revolvingFund?->update([
            'remaining_amount' => $remaining,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
