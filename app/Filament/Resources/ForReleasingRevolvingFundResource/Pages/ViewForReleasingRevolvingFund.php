<?php

namespace App\Filament\Resources\ForReleasingRevolvingFundResource\Pages;

use App\Filament\Resources\ForReleasingRevolvingFundResource;
use Facades\App\Services\RevolvingFund\ForReleasingRevolvingFundService;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewForReleasingRevolvingFund extends ViewRecord
{
    protected static string $resource = ForReleasingRevolvingFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('release')
                ->label('Release')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn ($record) => ForReleasingRevolvingFundService::canRelease($record))
                ->action(fn ($record) => ForReleasingRevolvingFundService::release($record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Revolving Fund Details')
                    ->schema([
                        TextEntry::make('fund_code')->label('Fund Code'),
                        TextEntry::make('addedBy.name')->label('Requestor'),
                        TextEntry::make('user.name')->label('Recipient'),
                        TextEntry::make('modeOfTransfer.name')->label('Mode of Transfer')->badge(),
                        TextEntry::make('initial_amount')->label('Initial Amount')->money('PHP'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('status_remarks')->label('Status Remarks')->badge(),
                        TextEntry::make('releasing_date')->label('Releasing Date')->date(),
                        TextEntry::make('remarks')->label('Remarks')->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Field Work Assignment')
                    ->schema([
                        TextEntry::make('field_work_assignment')
                            ->hiddenLabel()
                            ->state(function ($record) {
                                return collect($record->field_work_assignment ?? [])
                                    ->map(function ($item) {
                                        $day = ucfirst((string) ($item['day'] ?? ''));
                                        $from = $item['time_from'] ?? '-';
                                        $to = $item['time_to'] ?? '-';

                                        return "{$day}: {$from} - {$to}";
                                    })
                                    ->join('<br>');
                            })
                            ->html(),
                    ]),
                Section::make('Approval Form Details')
                    ->visible(fn ($record) => $record->revolvingFundApprovals()
                        ->whereNotNull('step_form_data')
                        ->exists())
                    ->schema([
                        TextEntry::make('custom_step_forms')
                            ->hiddenLabel()
                            ->state(fn ($record) => $this->renderCustomStepFormsHtml($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private function renderCustomStepFormsHtml($record): HtmlString
    {
        $approvals = $record->revolvingFundApprovals()
            ->with('approver')
            ->orderBy('step_order')
            ->orderBy('id')
            ->get()
            ->filter(fn ($approval) => is_array($approval->step_form_data) && ! empty($approval->step_form_data));

        if ($approvals->isEmpty()) {
            return new HtmlString('<span style="color:#6b7280;">No custom form values submitted.</span>');
        }

        $html = '<div style="display:flex;flex-direction:column;gap:12px;">';

        foreach ($approvals as $approval) {
            $role = ucwords(str_replace('_', ' ', (string) $approval->role_name));
            $status = ucfirst((string) $approval->status);
            $approverName = $approval->approver?->name ? e($approval->approver->name) : 'N/A';
            $actedAt = $approval->acted_at ? e($approval->acted_at->format('F d, Y h:i A')) : 'N/A';

            $html .= '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;">';
            $html .= '<div style="font-weight:600;margin-bottom:6px;">Step ' . (int) $approval->step_order . ' - ' . e($role) . '</div>';
            $html .= '<div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Status: ' . e($status) . ' | By: ' . $approverName . ' | At: ' . $actedAt . '</div>';
            $html .= '<ul style="margin:0;padding-left:18px;">';

            foreach ($approval->step_form_data as $key => $value) {
                $displayValue = blank($value) ? '-' : (is_scalar($value) ? (string) $value : json_encode($value));
                $html .= '<li><strong>' . e(ucwords(str_replace('_', ' ', (string) $key))) . ':</strong> ' . e($displayValue) . '</li>';
            }

            $html .= '</ul></div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }
}
