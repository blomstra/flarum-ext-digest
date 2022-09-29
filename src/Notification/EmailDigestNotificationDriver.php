<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Notification;

use Blomstra\Digest\Batch\BatchJobAggregator;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\EmailNotificationDriver;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\NotificationSyncer;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Queue\Queue;

class EmailDigestNotificationDriver extends EmailNotificationDriver
{
    /**
     * @var Queue We need to re-declare this variable because the one in the parent is private
     */
    protected $queue;

    protected $aggregator;
    protected $settings;
    protected $syncer;
    protected $excludedBlueprints;

    public function __construct(Queue $queue, BatchJobAggregator $aggregator, SettingsRepositoryInterface $settings, NotificationSyncer $syncer, array $excludedBlueprints)
    {
        parent::__construct($queue);

        $this->queue = $queue;
        $this->aggregator = $aggregator;
        $this->settings = $settings;
        $this->syncer = $syncer;
        $this->excludedBlueprints = $excludedBlueprints;
    }

    public function send(BlueprintInterface $blueprint, array $users): void
    {
        if ($this->settings->get('blomstra-digest.singleDigest')) {
            $this->syncer->onePerUser(function () {
                // No-op. This is just to reset NotificationSyncer::$sentTo and NotificationSyncer::$onePerUser
                // to disable the onePerUser feature of Flarum
            });
        }

        parent::send($blueprint, $users);
    }

    protected function mailNotifications(MailableInterface $blueprint, array $recipients)
    {
        if (in_array(get_class($blueprint), $this->excludedBlueprints)) {
            parent::mailNotifications($blueprint, $recipients);

            return;
        }

        foreach ($recipients as $user) {
            if ($user->shouldEmail($blueprint::getType())) {
                if ($user->digest_frequency) {
                    $this->queue->push(new SaveEmailForDigestJob($blueprint, $user));
                } else {
                    $this->aggregator->pushSyncBlueprint($blueprint, $user);
                }
            }
        }
    }
}
