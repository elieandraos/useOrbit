{{-- The default notification email, with the inviting organization's name in the header instead of the app name. --}}
<x-mail::layout>
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ $organizationName }}
</x-mail::header>
</x-slot:header>

# @lang('Hello!')

@foreach ($introLines as $line)
{{ $line }}

@endforeach

<x-mail::button :url="$actionUrl" color="primary">
{{ $actionText }}
</x-mail::button>

@foreach ($outroLines as $line)
{{ $line }}

@endforeach

@lang('Regards,')<br>
{{ config('app.name') }}

<x-slot:subcopy>
<x-mail::subcopy>
@lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-mail::subcopy>
</x-slot:subcopy>

<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
