@component('mail::layout')
{{-- Header --}}
@slot('header')
        @component('mail::header', ['url' => config('app.url')])
            <!-- header here -->
            <img src="{{asset(getSetting('site.light_logo'))}}" class="mail-logo" style="width:250px;">
        @endcomponent
@endslot

{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    switch ($level) {
        case 'success':
        case 'error':
            $color = $level;
            break;
        default:
            $color = 'primary';
    }
?>
@component('mail::button', ['url' => $actionUrl, 'color' => $color])
{{ $actionText }}
@endcomponent
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards'),<br>
{{ getSetting('emails.from_name') }}
@endif

{{-- Subcopy --}}
@isset($actionText)
@endisset

@slot('footer')
    @component('mail::footer')
        © {{ date('Y') }} {{getSetting('emails.from_name')}}. @lang('All rights reserved.')
@endcomponent
@endslot

@endcomponent
