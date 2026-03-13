<x-mail::message>
# Reimbursement Not Approved

Dear {{ $record->payee->name }},

Your reimbursement request has been **rejected**.

## Reimbursement Summary

**Reimbursement No.:** {{ $record->reimbursement_no }}
**Amount:** ₱{{ number_format($record->total_amount, 2) }}

@if($record->reason_for_rejection)
## Reason for Rejection

{{ $record->reason_for_rejection }}
@endif

<x-mail::button :url="route('filament.admin.resources.reimbursements.view', ['record' => $record])">
View Reimbursement
</x-mail::button>

Thank you,
{{ config('app.name') }}
</x-mail::message>

