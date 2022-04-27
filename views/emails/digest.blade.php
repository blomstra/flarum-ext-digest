{!! $translator->trans('blomstra-digest.email.greeting', [
'{recipient_display_name}' => $user->display_name,
]) !!}

@foreach($notifications as $notification)
## {{ $notification->date->format('Y-m-d H:i') }} - {{ $notification->blueprint->getEmailSubject($translator) }}

@php($notificationEmailView = \Illuminate\Support\Arr::get($notification->blueprint->getEmailView(), 'text'))
@include($notificationEmailView, ['blueprint' => $notification->blueprint])

---
@endforeach

{!! $translator->trans('blomstra-digest.email.footer') !!}
