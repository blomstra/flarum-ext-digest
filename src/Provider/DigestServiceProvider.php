<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Provider;

use Blomstra\Digest\Batch\BatchJobAggregator;
use Blomstra\Digest\Notification\EmailDigestNotificationDriver;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Suspend\Notification\UserSuspendedBlueprint;
use Flarum\Suspend\Notification\UserUnsuspendedBlueprint;
use FoF\Byobu\Notifications as ByobuNotifications;
use FoF\Subscribed\Blueprints\PostFlaggedBlueprint;

class DigestServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        // These blueprints will never be batched or templated by our extension
        // and will continue to be sent immediately
        $this->container->instance('blomstra.digest.excludedBlueprints', [
            UserSuspendedBlueprint::class,
            UserUnsuspendedBlueprint::class,
            ByobuNotifications\DiscussionAddedBlueprint::class,
            ByobuNotifications\DiscussionCreatedBlueprint::class,
            ByobuNotifications\DiscussionMadePublicBlueprint::class,
            ByobuNotifications\DiscussionRecipientRemovedBlueprint::class,
            ByobuNotifications\DiscussionRepliedBlueprint::class,
            PostFlaggedBlueprint::class,
        ]);

        $this->container->when(EmailDigestNotificationDriver::class)
            ->needs('$excludedBlueprints')
            ->give(function (): array {
                return $this->container->make('blomstra.digest.excludedBlueprints');
            });

        $this->container->singleton(BatchJobAggregator::class);
    }
}
