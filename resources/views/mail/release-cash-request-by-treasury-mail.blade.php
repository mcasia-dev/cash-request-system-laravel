<x-mail::message>
# Cash Request Released

Dear {{ $record->user->name }},

Your cash request has been **released** and is now **ready for pickup**. Please proceed with the next steps as advised by the Treasury department.

## Request Summary

**Request No.:** {{ $record->request_no }}

**Amount Released:** {{ number_format($record->requesting_amount, 2) }}

<x-mail::button :url="route('filament.admin.resources.cash-requests.track-status', ['record' => $record])">
View Request Status
</x-mail::button>

If you have any questions or need further assistance, please don't hesitate to contact us.

Thank you for your cooperation.

Best regards,
{{ config('app.name') }}

---
<p style="font-size: 12px; color: #6b7280;">
This is an automated message from {{ config('app.name') }}. Please do not reply directly to this email.
</p>
</x-mail::message>
