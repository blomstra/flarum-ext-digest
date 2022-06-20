<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Mail;

use Flarum\Discussion\Discussion as FlarumDiscussion;
use Flarum\Post\Post as FlarumPost;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Helper class to define why a discussion is part of the digest.
 */
class Discussion
{
    /**
     * @var FlarumDiscussion The discussion model. Will be set by the digest code before passed to the template
     */
    public $discussion = null;

    /**
     * @var bool Whether this discussion is part of a flarum/subscriptions notification
     */
    public $isFollowed = false;

    /**
     * @var bool Whether this discussion is part of an fof/follow-tags notification in "follow" mode
     */
    public $isTagFollowed = false;

    /**
     * @var bool Whether this discussion is part of an fof/follow-tags notification in "lurk" mode
     */
    public $isTagLurked = false;

    /**
     * @var Post[] Whether this discussion is part of a flarum/subscriptions notification
     */
    protected $importantPosts = [];

    public function __construct(FlarumDiscussion $discussion)
    {
        $this->discussion = $discussion;
    }

    /**
     * Helper function to update an important post's information and create it if necessary.
     *
     * @param int $id Post database ID
     *
     * @return Post
     */
    public function importantPost(int $id): Post
    {
        if (array_key_exists($id, $this->importantPosts)) {
            return $this->importantPosts[$id];
        }

        $post = new Post();

        $this->importantPosts[$id] = $post;

        return $post;
    }

    /**
     * To be called in the template to get which posts are visible in the final email.
     *
     * @param User $user
     *
     * @return array
     */
    public function relevantPosts(User $user): array
    {
        $flarumPosts = $this->discussion->comments()
            ->orderBy('number')
            ->where(function (Builder $query) use ($user) {
                $query->whereIn('posts.id', array_keys($this->importantPosts));

                // If the discussion is followed or the tag lurked, always include all posts the user has not yet read
                // TODO: we should store what is the last post sent in the digest so it's not sent again in the next digest
                if ($this->isFollowed || $this->isTagLurked) {
                    $lastReadPostNumber = $this->discussion->readers()
                        ->where('users.id', $user->id)
                        ->pluck('last_read_post_number');

                    $query->orWhere('number', '>', $lastReadPostNumber ?? 0);
                }

                // The tag is marked as followed if there was a new discussion notification. In this case we'll include the first post
                if ($this->isTagFollowed) {
                    $query->orWhere('number', 1);
                }
            })
            ->whereVisibleTo($user)
            ->get();

        return array_map(function (FlarumPost $flarumPost) {
            // For new posts that weren't important posts, a new blank post information is used
            $postInfo = $this->importantPosts[$flarumPost->id] ?? new Post();

            $postInfo->post = $flarumPost;

            return $postInfo;
        }, $flarumPosts->all());
    }
}
