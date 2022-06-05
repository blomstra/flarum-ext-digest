<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Listener;

use Blomstra\Digest\Job\SendReplyNotificationToOthers;
use Flarum\Extension\ExtensionManager;
use Flarum\Post\Event\Posted;
use Illuminate\Contracts\Queue\Queue;

/**
 * Same as Flarum\Subscriptions\Listener\SendNotificationWhenReplyIsPosted to dispatch the modified job
 * With added check for whether the extension is enabled.
 */
class SendFollowReplyNotificationEveryTime
{
    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var ExtensionManager
     */
    protected $manager;

    public function __construct(Queue $queue, ExtensionManager $manager)
    {
        $this->queue = $queue;
        $this->manager = $manager;
    }

    public function handle(Posted $event)
    {
        if (!$this->manager->isEnabled('flarum-subscriptions')) {
            return;
        }

        $this->queue->push(
            new SendReplyNotificationToOthers($event->post, $event->post->discussion->last_post_number)
        );
    }
}
