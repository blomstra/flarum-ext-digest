@extends('blomstra-digest::emails.layouts.basic')

@section('content')
    <div class="container">
        <p>
            {{ $translator->trans('blomstra-digest.email.digest.greeting', [
                '{recipient_display_name}' => $user->display_name,
            ]) }}
        </p>

        <p>
            {{ $translator->trans('blomstra-digest.email.digest.summary', [
                '{discussionCount}' => count($discussions),
                '{notificationCount}' => $notificationCount,
            ]) }}
        </p>
    </div>

    @foreach($discussions as $discussion)
        @include('blomstra-digest::emails.content.discussion', ['discussion' => $discussion])
    @endforeach

    @include('blomstra-digest::emails.content.other-notifications', ['notifications' => $otherNotifications])

    <div class="container">
        <p>{{ $translator->trans('blomstra-digest.email.' . ($single ? 'single' : 'digest') . '.footer') }}</p>
    </div>
@endsection
