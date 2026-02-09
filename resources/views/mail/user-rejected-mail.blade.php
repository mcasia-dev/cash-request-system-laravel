<x-mail::message>
# Account not approved

Dear **{{ $name }}**,

We reviewed your registration and, unfortunately, your account was not approved.

@isset($reason)
**Reason:** {{ $reason }}
@endisset

If you believe this is a mistake or would like to appeal, please contact us.

Thanks,<br>
{{ config('app.name') }}

&copy; {{ date('Y') }} McAsia FoodTrade Corporation. All rights reserved.
</x-mail::message>
