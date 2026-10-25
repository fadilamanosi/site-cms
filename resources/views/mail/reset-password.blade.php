<x-mail::message>
You are receiving this email because we received a password reset request for your Site CMS account.

<x-mail::button :url="$url">
Reset password
</x-mail::button>

This link expires in {{ $expireMinutes }} minutes. If you did not request a reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
