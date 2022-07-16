@if (count($notifications))
    <div class="Hero">
        <div class="container">
            <h2>{{ $translator->trans('blomstra-digest.email.digest.nonDiscussionGroup') }}</h2>
        </div>
    </div>

    <div class="container">
        @foreach($notifications as $notification)
            <h3>{{ $notification->title($translator) }}</h3>

            {!! $notification->render($user) !!}

            <hr>
        @endforeach
    </div>
@endif
