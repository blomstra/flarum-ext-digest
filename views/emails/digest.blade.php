<!doctype html>
<html>
<body>

<p>
    {{ $translator->trans('blomstra-digest.email.digest.greeting', [
        '{recipient_display_name}' => $user->display_name,
    ]) }}
</p>

@foreach($groupedNotifications as $group)
    <h2>
        @if ($group->discussion)
            <a href="{{ $url->to('forum')->route('discussion', [
                'id' =>  resolve(\Flarum\Http\SlugManager::class)->forResource(\Flarum\Discussion\Discussion::class)->toSlug($group->discussion),
            ]) }}">
                {{ $group->discussion->title }}
            </a>
        @else
            {{ $translator->trans('blomstra-digest.email.digest.nonDiscussionGroup') }}
        @endif
    </h2>

    @foreach($group->notifications as $notification)
        <h3>{{ $notification->date->format('Y-m-d H:i') }}
            - {{ $notification->blueprint->getEmailSubject($translator) }}</h3>

        {!! $notification->render($user) !!}

        <hr>
    @endforeach
@endforeach

<p>{{ $translator->trans('blomstra-digest.email.digest.footer') }}</p>

</body>
</html>
