<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Extend;
use Flarum\User\Event\Saving;
use Flarum\User\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Queue\Events as QueueEvents;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View())
        ->namespace('blomstra-digest', __DIR__.'/views'),

    (new Extend\Event())
        ->listen(QueueEvents\JobQueued::class, Listener\AddQueuedJobsToBatch::class)
        ->listen(QueueEvents\JobProcessing::class, Listener\WatchBatchJobForProcessing::class)
        ->listen(QueueEvents\JobProcessed::class, Listener\WatchBatchJobsForCompletion::class)
        ->listen(QueueEvents\JobFailed::class, Listener\WatchBatchJobsForCompletion::class)
        ->listen(Saving::class, Listener\SaveUser::class),

    (new Extend\ApiSerializer(CurrentUserSerializer::class))
        ->attribute('digestFrequency', function ($serializer, User $user) {
            return $user->digest_frequency;
        })
        ->attribute('digestHour', function ($serializer, User $user) {
            return $user->digest_hour;
        }),

    (new Extend\Model(User::class))
        ->cast('last_digest_sent_at', 'datetime')
        ->cast('digest_hour', 'integer'),

    (new Extend\Notification())
        ->driver('email', Notification\EmailDigestNotificationDriver::class),

    (new Extend\Console())
        ->command(Console\SendDigestCommand::class)
        ->schedule('digest:send', function (Event $event) {
            $event->hourly();
        }),

    (new Extend\ServiceProvider())
        ->register(Provider\DigestServiceProvider::class),

    (new Extend\Middleware('api'))
        ->add(Middleware\SingleDigestBatchLifecycle::class),
];
