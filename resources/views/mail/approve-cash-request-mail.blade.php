<x-mail::message>
# Cash Request Status

Dear {{ $record->user->name }},

We are pleased to inform you that your cash request has been **approved** by
{{ \Illuminate\Support\Str::of($user->getRoleNames()->first() ?? 'Admin')->replace('_', ' ')->title() }}.

## Request Summary

**Request No.:** {{ $record->request_no }}
**Amount Approved:** ₱{{ number_format($record->requesting_amount, 2) }}

<x-mail::button :url="route('filament.admin.resources.cash-requests.track-status', ['record' => $record])">
View Request Status
</x-mail::button>

If you have any questions or need further assistance, please don't hesitate to contact us.

Thank you for your cooperation and prompt attention.

Best regards,
{{ config('app.name') }}

---
<p style="font-size: 12px; color: #6b7280;">
This is an automated message from {{ config('app.name') }}. Please do not reply directly to this email.
</p>
</x-mail::message>

