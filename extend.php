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
        ->listen(Saving::class, Listener\SaveUser::class),

    (new Extend\ApiSerializer(CurrentUserSerializer::class))
        ->attribute('digestFrequency', function ($serializer, User $user) {
            return $user->digest_frequency;
        }),

    (new Extend\Notification())
        ->driver('email', Notification\EmailDigestNotificationDriver::class),

    (new Extend\Console())
        ->command(Console\SendDigestCommand::class)
        ->schedule('digest:send daily', function(Event $event) {
            $event->daily();
        })
        ->schedule('digest:send weekly', function(Event $event) {
            $event->weekly();
        }),
];
