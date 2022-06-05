<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Job;

use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Post;
use Flarum\Subscriptions\Notification\NewPostBlueprint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Modified version of Flarum\Subscriptions\Job\SendReplyNotification (original job still runs as well)
 * This code will send the notification to all users who weren't already notified by the original job
 * TODO: The original code was modified in https://github.com/flarum/framework/pull/3445 which will be released in 1.3.1
 * This will require some kind of detection of Flarum 1.2 vs 1.3.
 */
class SendReplyNotificationToOthers implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @var Post
     */
    protected $post;

    /**
     * @var int
     */
    protected $lastPostNumber;

    /**
     * @param Post     $post
     * @param int|null $lastPostNumber
     */
    public function __construct(Post $post, $lastPostNumber)
    {
        $this->post = $post;
        $this->lastPostNumber = $lastPostNumber;
    }

    public function handle(NotificationSyncer $notifications)
    {
        $post = $this->post;
        $discussion = $post->discussion;

        $notify = $discussion->readers()
            ->where('users.id', '!=', $post->user_id)
            ->whereNotNull('users.digest_frequency') // Only do this for users who enabled digest
            ->where('discussion_user.subscription', 'follow')
            ->where('discussion_user.last_read_post_number', '!=', $this->lastPostNumber) // Opposite of original condition
            ->get();

        $notifications->sync(
            new NewPostBlueprint($post),
            $notify->all()
        );
    }
}
