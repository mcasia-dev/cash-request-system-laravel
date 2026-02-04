<x-mail::message>
# Cash Request Not Approved

Dear {{ $record->user->name }},

We regret to inform you that your cash request has **not been approved**.

## Request Details

**Request No.:** {{ $record->request_no }}

**Activity Name:** {{ $record->activity_name }}

**Activity Date:** {{ $record->activity_date->format('F d, Y') }}

**Activity Venue:** {{ $record->activity_venue }}

**Amount Requested:** ₱{{ number_format($record->requesting_amount, 2) }}

@if($record->reason_for_rejection)
## Reason for Rejection

{{ $record->reason_for_rejection }}
@endif

## Next Steps

If you believe this decision was made in error, please contact us to discuss further. You may also submit a new cash request with updated information if needed.

<x-mail::button :url="route('filament.admin.resources.cash-requests.index')">
Submit New Request
</x-mail::button>

Thank you for your understanding.

Best regards,
{{ config('app.name') }}

---
<p style="font-size: 12px; color: #6b7280;">
This is an automated message from {{ config('app.name') }}. Please do not reply directly to this email.
</p>
</x-mail::message>
