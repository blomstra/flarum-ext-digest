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

use Flarum\Notification\Driver\EmailNotificationDriver;
use Flarum\Notification\Job\SendEmailNotificationJob;
use Flarum\Notification\MailableInterface;
use Illuminate\Contracts\Queue\Queue;

class EmailDigestNotificationDriver extends EmailNotificationDriver
{
    /**
     * @var Queue We need to re-declare this variable because the one in the parent is private
     */
    protected $queue;

    public function __construct(Queue $queue)
    {
        parent::__construct($queue);

        $this->queue = $queue;
    }

    protected function mailNotifications(MailableInterface $blueprint, array $recipients)
    {
        foreach ($recipients as $user) {
            if ($user->shouldEmail($blueprint::getType())) {
                if ($user->digest_frequency) {
                    $this->queue->push(new SaveEmailForDigestJob($blueprint, $user));
                } else {
                    $this->queue->push(new SendEmailNotificationJob($blueprint, $user));
                }
            }
        }
    }
}
