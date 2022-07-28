<?php
$discussionHeroStyle = '';
$tags = [];

if (resolve(\Flarum\Extension\ExtensionManager::class)->isEnabled('flarum-tags')) {
    /**
     * @var \Illuminate\Database\Eloquent\Collection $unsortedTags
     */
    $unsortedTags = $discussion->discussion->tags;
    $unsortedTags->load('parent');

    // PHP implementation of the frontend's flarum/tags/common/utils/sortTags() method
    $tags = $unsortedTags
        ->sortByDesc('discussion_count') // Sort for secondary tags which won't be affected by the other sorts
        ->sortBy('position') // Sorts primary tags, this will persist as the primary child order
        ->sortBy(function($tag) {
            if ($tag->parent) {
                return $tag->parent->position;
            }

            // Puts secondary tags at the end
            if ($tag->position === null) {
                return PHP_INT_MAX;
            }

            return $tag->position;
        }); // Sorts by parent order

    if ($tags->count() && $tags->first()->color) {
        $discussionHeroStyle = 'background-color: ' .  $tags->first()->color;
    }
}
?>
<div class="Hero DiscussionHero" style="{{ $discussionHeroStyle }}">
    <div class="container">
        @foreach($tags as $tag)
            <span class="TagLabel">{{ $tag->name }}</span>
        @endforeach
        <h2>
            <a href="{{ $url->to('forum')->route('discussion', [
                    'id' =>  resolve(\Flarum\Http\SlugManager::class)->forResource(\Flarum\Discussion\Discussion::class)->toSlug($discussion->discussion),
                ]) }}" style="color: #333; text-decoration: none">
                {{ $discussion->discussion->title }}
            </a>
        </h2>
    </div>
</div>

{{-- TODO: show discussion author --}}

<div class="container">
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
        <div class="Post">
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

    <div style="text-align: center; margin-bottom: 20px;">
        <a href="{{ $url->to('forum')->route('discussion', [
                    'id' =>  resolve(\Flarum\Http\SlugManager::class)->forResource(\Flarum\Discussion\Discussion::class)->toSlug($discussion->discussion),
                ]) }}" style="color: #fff; background: #4d698e; text-decoration: none; padding: 8px 20px; line-height: 20px; border-radius: 4px;">
            {{ $translator->trans('blomstra-digest.email.digest.discussion.visit') }}
        </a>
    </div>
</div>
