<p>{{ $translator->trans('blomstra-digest.email.newPost.message', [
    '{poster_display_name}' => $blueprint->post->user->display_name,
]) }}</p>

@if ($blueprint->post instanceof \Flarum\Post\CommentPost)
    {!! $blueprint->post->formatContent() !!}
@else
    {{ $blueprint->post->content }}
@endif

<a href="{{ $url->to('forum')->route('discussion', [
                'id' =>  resolve(\Flarum\Http\SlugManager::class)->forResource(\Flarum\Discussion\Discussion::class)->toSlug($blueprint->post->discussion),
                'near' => $blueprint->post->number,
            ]) }}">
    {{ $translator->trans('blomstra-digest.email.newPost.link') }}
</a>
