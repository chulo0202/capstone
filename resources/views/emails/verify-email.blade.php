<x-mail::layout>
<x-slot:header>
<x-mail::header :url="config('app.url')">
FAMS
</x-mail::header>
</x-slot:header>

# Verify your FAMS email address

Confirm this email address for your Farmer Assistance Management System (FAMS) account.

<x-mail::button :url="$verificationUrl" color="success">
Verify Email
</x-mail::button>

This link expires in {{ $expirationMinutes }} minutes. If you did not request email verification, you can ignore this message.

<x-slot:subcopy>
<x-mail::subcopy>
If the button does not open, copy this link into your browser:

[{{ $verificationUrl }}]({{ $verificationUrl }})
</x-mail::subcopy>
</x-slot:subcopy>

<x-slot:footer>
<x-mail::footer>
Farmer Assistance Management System (FAMS)
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
