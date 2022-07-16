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

use Blomstra\Digest\Notification\SendSingleDigestJob;
use Flarum\Discussion\Discussion;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Job\SendEmailNotificationJob;
use Flarum\Notification\MailableInterface;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;

/**
 * Queues new notifications in memory to group them as needed
 */
class MemoryQueue
{
    protected $enabled = false;

    protected $discussionGroups = [];
    protected $postGroups = [];

    protected $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * The current implementation relies on a middleware to send pending blueprints
     * This will only work when processing web requests
     * So we need to disable it by default to make sure we don't accidentally suppress notifications sent by commands and async jobs
     * or other web contexts where the middleware never ran
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * @param BlueprintInterface&MailableInterface $blueprint
     * @param User $recipient
     */
    public function push(BlueprintInterface $blueprint, User $recipient): void
    {
        if (!$this->enabled) {
            // If the memory queue is not supported in the current context, always send regular mails
            $this->queue->push(new SendEmailNotificationJob($blueprint, $recipient));

            return;
        }

        $subject = $blueprint->getSubject();

        if ($subject instanceof Discussion) {
            $this->discussionGroups[$recipient->id][$subject->id][] = $blueprint;
        } else if ($subject instanceof Post) {
            $this->postGroups[$recipient->id][$subject->id][] = $blueprint;
        } else {
            // If the blueprint can't be grouped in memory, send the email using the regular job
            $this->queue->push(new SendEmailNotificationJob($blueprint, $recipient));
        }
    }

    /**
     * Sends the blueprints still in memory
     * They will be sent with the "single" digest template, with one per user per discussion/post
     * The single digest template is used even if there's a single notification for a discussion/post for consistency
     */
    public function send(): void
    {
        foreach ($this->discussionGroups as $userId => $groups) {
            $recipient = User::find($userId);

            foreach ($groups as $blueprints) {
                $this->queue->push(new SendSingleDigestJob($blueprints, $recipient));
            }
        }

        foreach ($this->postGroups as $userId => $groups) {
            $recipient = User::find($userId);

            foreach ($groups as $blueprints) {
                $this->queue->push(new SendSingleDigestJob($blueprints, $recipient));
            }
        }
    }
}
