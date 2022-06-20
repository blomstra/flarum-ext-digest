<!doctype html>
<html>
<head>
    <style>
        body {
            color: #111;
            font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Ubuntu,Cantarell,Oxygen,Roboto,Helvetica,Arial,sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>

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

@foreach($discussions as $discussion)
    <h2>
        <a href="{{ $url->to('forum')->route('discussion', [
            'id' =>  resolve(\Flarum\Http\SlugManager::class)->forResource(\Flarum\Discussion\Discussion::class)->toSlug($discussion->discussion),
        ]) }}">
            {{ $discussion->discussion->title }}
        </a>
    </h2>

    {{-- TODO: show discussion author and tag --}}

    @if ($discussion->isFollowed)
        <p style="color: rgb(102, 124, 153);">{{ $translator->trans('blomstra-digest.email.digest.discussion.followed') }}</p>
    @endif

    @if ($discussion->isTagFollowed)
        <p style="color: rgb(102, 124, 153);">{{ $translator->trans('blomstra-digest.email.digest.discussion.tagFollowed') }}</p>
    @endif

    @if ($discussion->isTagLurked)
        <p style="color: rgb(102, 124, 153);">{{ $translator->trans('blomstra-digest.email.digest.discussion.tagLurked') }}</p>
    @endif

    @foreach($discussion->relevantPosts($user) as $post)
        <div class="Post" style="border: 2px solid #aaa; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            @php($author = $post->post->user)

            <div class="PostHeader">
                @if ($author)
                    <a href="{{ $url->to('forum')->route('user', [
                        'username' =>  resolve(\Flarum\Http\SlugManager::class)->forResource(\Flarum\User\User::class)->toSlug($author),
                    ]) }}" style="font-weight: bold; color: rgb(17, 17, 17); text-decoration: none;">
                        {{ $author->display_name }}
                    </a>
                @else
                    [deleted user]
                @endif

                <a href="{{ $url->to('forum')->route('discussion', [
                        'id' =>  resolve(\Flarum\Http\SlugManager::class)->forResource(\Flarum\Discussion\Discussion::class)->toSlug($discussion->discussion),
                        'near' => $post->post->number,
                    ]) }}" style="color: rgb(102, 124, 153); text-decoration: none;">
                    {{ $post->post->created_at->format('Y-m-d H:i') }}
                </a>
            </div>

            @if ($post->isMentioned)
                <p style="color: rgb(102, 124, 153);">{{ $translator->trans('blomstra-digest.email.digest.post.mentioned') }}</p>
            @endif

            <div class="PostBody">
                @if ($post->post instanceof \Flarum\Post\CommentPost)
                    {!! $post->post->formatContent() !!}
                @else
                    {{ $post->post->content }}
                @endif
            </div>
        </div>
    @endforeach
@endforeach

@if (count($otherNotifications))
    <h2>{{ $translator->trans('blomstra-digest.email.digest.nonDiscussionGroup') }}</h2>

    @foreach($otherNotifications as $notification)
        <h3>{{ $notification->title($translator) }}</h3>

        {!! $notification->render($user) !!}

        <hr>
    @endforeach
@endif

<p>{{ $translator->trans('blomstra-digest.email.digest.footer') }}</p>

</body>
</html>
