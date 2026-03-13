<x-mail::message>
# Reimbursement Status

Dear {{ $record->payee->name }},

Your reimbursement request has been **approved**.

## Reimbursement Summary

**Reimbursement No.:** {{ $record->reimbursement_no }}
**Amount:** ₱{{ number_format($record->total_amount, 2) }}

<x-mail::button :url="route('filament.admin.resources.reimbursements.view', ['record' => $record])">
View Reimbursement
</x-mail::button>

Thank you,
{{ config('app.name') }}
</x-mail::message>

