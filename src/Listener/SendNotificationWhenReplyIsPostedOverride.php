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
use Flarum\Settings\SettingsRepositoryInterface;
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

    /**
     * @var Queue
     */
    protected $sync;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(Queue $queue, SyncQueue $sync, Container $container, SettingsRepositoryInterface $settings)
    {
        $this->queue = $queue;
        $this->sync = $sync;
        $this->sync->setContainer($container);
        $this->settings = $settings;
    }

    public function handle(Posted $event)
    {
        $job = new SendReplyNotification($event->post, $event->post->discussion->last_post_number);

        if ($this->settings->get('blomstra-digest.singleDigest')) {
            $this->sync->push($job);
        } else {
            $this->queue->push($job);
        }
    }
}
