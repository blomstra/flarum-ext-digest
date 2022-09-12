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

use Blomstra\Digest\Batch\BatchJobAggregator;
use Illuminate\Queue\Events\JobQueued;

class AddQueuedJobsToBatch
{
    protected $aggregator;

    const EVENTS_TO_BATCH = [
        \Flarum\Subscriptions\Job\SendReplyNotification::class,
        \FoF\Subscribed\Jobs\SendNotificationWhenDiscussionIsStarted::class,
        \FoF\Subscribed\Jobs\SendNotificationWhenPostIsCreated::class,
        \FoF\Subscribed\Jobs\SendNotificationWhenPostIsFlagged::class,
        \FoF\Subscribed\Jobs\SendNotificationWhenPostIsUnapproved::class,
        \FoF\Subscribed\Jobs\SendNotificationWhenUserIsCreated::class,
    ];

    public function __construct(BatchJobAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    public function handle(JobQueued $event)
    {
        $jobClass = get_class($event->job);

        if (in_array($jobClass, self::EVENTS_TO_BATCH)) {
            $this->aggregator->attachAsyncJob($event->id);
        }
    }
}
