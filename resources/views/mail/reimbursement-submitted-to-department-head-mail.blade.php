<x-mail::message>
# Reimbursement For Approval

Dear {{ $departmentHead->name }},

A new reimbursement request is waiting for your approval.

## Request Summary

**Reimbursement No.:** {{ $record->reimbursement_no }}
**Requestor:** {{ $record->payee->name }}
**Amount:** ₱{{ number_format($record->total_amount, 2) }}

<x-mail::button :url="route('filament.admin.resources.for-approval-reimbursements.view', ['record' => $record])">
Review Request
</x-mail::button>

Thank you,
{{ config('app.name') }}
</x-mail::message>

