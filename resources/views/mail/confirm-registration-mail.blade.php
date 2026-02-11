<x-mail::message>
# Account created successfully!

Dear **{{ $name }}**,

Thank you for registering with us! We're excited to have you on board.

## Account Status

Your account registration has been successfully received. Your account is currently **under review** by our department head. We will verify your information and notify you about its status.

Please allow us some time to review your registration details.

---

**Control Number:** {{ $control_no }}

---

If you have any questions or need further assistance, please don't hesitate to contact us.

Thanks,<br>
{{ config('app.name') }}

&copy; {{ date('Y') }} McAsia FoodTrade Corporation. All rights reserved.
</x-mail::message>
