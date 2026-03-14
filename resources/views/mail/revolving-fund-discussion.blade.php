<x-mail::message>
# {{ $title }}

{{ $messageBody }}

@if(! empty($actionUrl) && ! empty($actionLabel))
<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
