<x-mail::message>
# Cash Request Approved

Dear {{ $record->user->name }},

We are pleased to inform you that your cash request has been **approved**.

## Request Details

**Request No.:** {{ $record->request_no }}

**Activity Name:** {{ $record->activity_name }}

**Activity Date:** {{ $record->activity_date->format('F d, Y') }}

**Activity Venue:** {{ $record->activity_venue }}

**Amount Approved:** ₱{{ number_format($record->requesting_amount, 2) }}

## Payment Information

**Payment Type:** {{ ucfirst($record->nature_of_payment) }}

**Payee:** {{ $record->payee }}

**Payment To:** {{ ucfirst(str_replace('_', ' ', $record->payment_to)) }}

## Important Dates

**Due Date:** {{ $record->due_date }}

<x-mail::button :url="route('filament.admin.resources.cash-requests.track-status', ['record' => $record])">
View Request Status
</x-mail::button>

Please ensure to liquidate the amount by the due date. If you have any questions, please contact the Finance department.

Thank you for your cooperation.

Best regards,
{{ config('app.name') }}

---
<p style="font-size: 12px; color: #6b7280;">
This is an automated message from {{ config('app.name') }}. Please do not reply directly to this email.
</p>
</x-mail::message>
