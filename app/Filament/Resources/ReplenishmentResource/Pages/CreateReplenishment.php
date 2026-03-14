<?php

namespace App\Filament\Resources\ReplenishmentResource\Pages;

use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use App\Filament\Resources\ReplenishmentResource;
use App\Services\RevolvingFund\ReplenishmentApprovalFlowService;
use App\Services\RevolvingFund\ReplenishmentApprovalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CreateReplenishment extends CreateRecord
{
    protected static string $resource = ReplenishmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $initial = (float)($data['initial_amount'] ?? 0);
        $total = collect($data['replenishmentItems'] ?? [])
            ->sum(fn($item) => (float)($item['amount'] ?? 0));

        $data['total_amount'] = $total;
        $data['remaining_amount'] = max($initial - $total, 0);
        $data['status'] = 'pending';
        $data['status_remarks'] = 'Submitted for Approval';

        $preview = new \App\Models\RevolvingFund\Replenishment([
            'total_amount' => $total,
        ]);

        if (!app(ReplenishmentApprovalFlowService::class)->resolveRule($preview)) {
            throw new RuntimeException('No active replenishment approval rule found. Configure one first.');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $total = (float)$this->record->replenishmentItems()->sum('amount');
        $initial = (float)$this->record->initial_amount;
        $remaining = max($initial - $total, 0);

        $this->record->update([
            'total_amount' => $total,
            'remaining_amount' => $remaining,
        ]);

        $this->record->revolvingFund?->update([
            'remaining_amount' => $remaining,
            'status' => Status::IN_PROGRESS->value,
            'status_remarks' => StatusRemarks::FOR_REPLENISHMENT->value,
        ]);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($this->record->revolvingFund ?? $this->record)
            ->event('replenishment_submitted')
            ->withProperties([
                'replenishment_id' => $this->record->id,
                'fund_code' => $this->record->revolvingFund?->fund_code,
                'initial_amount' => $this->record->initial_amount,
                'total_amount' => $this->record->total_amount,
                'remaining_amount' => $this->record->remaining_amount,
                'status' => $this->record->status,
                'status_remarks' => $this->record->status_remarks,
            ])
            ->log("Replenishment request was submitted for {$this->record->revolvingFund?->fund_code}.");

        app(ReplenishmentApprovalService::class)->notifyDepartmentHeadsOnSubmission($this->record->fresh(['revolvingFund.user']));
    }
}
