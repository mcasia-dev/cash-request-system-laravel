<x-mail::message>
# Reimbursement Accounting Update

{{ $messageBody }}

## Request Summary

**Reimbursement No.:** {{ $record->reimbursement_no }}
**Requestor:** {{ $record->payee?->name ?? 'N/A' }}
**Amount:** ₱{{ number_format((float) $record->total_amount, 2) }}

<x-mail::button :url="$actionUrl">
View Request
</x-mail::button>

Thank you,<br>
{{ config('app.name') }}
</x-mail::message>

