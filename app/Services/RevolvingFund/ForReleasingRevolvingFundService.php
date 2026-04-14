<?php

namespace App\Services\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ForReleasingRevolvingFundService
{
    public function canRelease($record): bool
    {
        return $record->status === Status::APPROVED->value
            && $record->status_remarks === StatusRemarks::FOR_RELEASING->value
            && $this->canManageRelease();
    }

    public function release($record)
    {
        $user = Auth::user();

        $record->update([
            'status' => Status::RELEASED->value,
            'status_remarks' => StatusRemarks::FUND_RELEASED->value,
            'released_by' => $user?->id,
            'released_at' => now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('released')
            ->withProperties([
                'fund_code' => $record->fund_code,
                'new_status' => Status::RELEASED->value,
                'status_remarks' => StatusRemarks::FUND_RELEASED->value,
            ])
            ->log("Revolving fund {$record->fund_code} was released by {$user->name} ({$user->position})");

        if ($record->addedBy) {
            Notification::make()
                ->title('Revolving Fund Released')
                ->body("Your revolving fund {$record->fund_code} has been released.")
                ->actions([
                    NotificationAction::make('markAsRead')
                        ->button()
                        ->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.revolving-funds.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->addedBy);
        }

        Notification::make()
            ->title('Revolving fund released')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-releasing-revolving-funds.index');
    }

    private function canManageRelease(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles()->whereIn('name', ['treasury_staff', 'treasury_manager'])->exists();
    }


    public function renderCustomStepFormsHtml($record): HtmlString
    {
        $approvals = $record->revolvingFundApprovals()
            ->with('approver')
            ->orderBy('step_order')
            ->orderBy('id')
            ->get()
            ->filter(fn($approval) => is_array($approval->step_form_data) && !empty($approval->step_form_data));

        if ($approvals->isEmpty()) {
            return new HtmlString('<span style="color:#6b7280;">No custom form values submitted.</span>');
        }

        $html = '<div style="display:flex;flex-direction:column;gap:12px;">';

        foreach ($approvals as $approval) {
            $role = ucwords(str_replace('_', ' ', (string)$approval->role_name));
            $status = ucfirst((string)$approval->status);
            $approverName = $approval->approver?->name ? e($approval->approver->name) : 'N/A';
            $actedAt = $approval->acted_at ? e($approval->acted_at->format('F d, Y h:i A')) : 'N/A';

            $html .= '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;">';
            $html .= '<div style="font-weight:600;margin-bottom:6px;">Step ' . (int)$approval->step_order . ' - ' . e($role) . '</div>';
            $html .= '<div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Status: ' . e($status) . ' | By: ' . $approverName . ' | At: ' . $actedAt . '</div>';
            $html .= '<ul style="margin:0;padding-left:18px;">';

            foreach ($approval->step_form_data as $key => $value) {
                $displayValue = blank($value) ? '-' : (is_scalar($value) ? (string)$value : json_encode($value));
                $html .= '<li><strong>' . e(ucwords(str_replace('_', ' ', (string)$key))) . ':</strong> ' . e($displayValue) . '</li>';
            }

            $html .= '</ul></div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }


    /**
     * @param $record
     * @return string
     */
    public function getFieldOfWorkAssignmentState($record)
    {
        return collect($record->field_work_assignment ?? [])
            ->map(function ($item) {
                $day = ucfirst((string)($item['day'] ?? ''));
                $from = $item['time_from'] ?? '-';
                $to = $item['time_to'] ?? '-';

                return "{$day}: {$from} - {$to}";
            })
            ->join('<br>');
    }
}
