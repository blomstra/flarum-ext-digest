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

use Flarum\Post\Event\Posted;
use Flarum\Subscriptions\Job\SendReplyNotification;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\SyncQueue;

// Same as original from flarum/subscriptions except we force it to use sync queue
class SendNotificationWhenReplyIsPostedOverride
{
    /**
     * @var Queue
     */
    protected $queue;

    public function __construct(SyncQueue $queue, Container $container)
    {
        $this->queue = $queue;
        $this->queue->setContainer($container);
    }

    public function handle(Posted $event)
    {
        $this->queue->push(
            new SendReplyNotification($event->post, $event->post->discussion->last_post_number)
        );
    }
}
