<x-mail::message>
# Account approved

Dear **{{ $name }}**,

Your account has been approved. You can now sign in and start using the system.

---

**Control Number:** {{ $control_no }}

---

If you have any questions or need further assistance, please don't hesitate to contact us.

Thanks,<br>
{{ config('app.name') }}

&copy; {{ date('Y') }} McAsia FoodTrade Corporation. All rights reserved.
</x-mail::message>
