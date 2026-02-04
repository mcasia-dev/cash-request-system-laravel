<x-mail::message>
# Cash Request Released

Dear {{ $record->user->name }},

Your cash request has been **released** by the Treasury team and is now **ready for pickup**. Please proceed with the next steps as advised by the Treasury department.

## Request Summary

**Request No.:** {{ $record->request_no }}

**Activity Name:** {{ $record->activity_name }}

**Activity Date:** {{ $record->activity_date->format('F d, Y') }}

**Activity Venue:** {{ $record->activity_venue }}

**Amount Released:** â‚±{{ number_format($record->requesting_amount, 2) }}

<x-mail::button :url="route('filament.admin.resources.cash-requests.track-status', ['record' => $record])">
View Request Status
</x-mail::button>

If you have any questions, please contact the Treasury department.

Thank you for your cooperation.

Best regards,
{{ config('app.name') }}

---
<p style="font-size: 12px; color: #6b7280;">
This is an automated message from {{ config('app.name') }}. Please do not reply directly to this email.
</p>
</x-mail::message>
