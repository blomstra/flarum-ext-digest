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

use Blomstra\Digest\QueuedBlueprint;
use Carbon\Carbon;
use Flarum\Notification\MailableInterface;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;

class SaveEmailForDigestJob extends AbstractJob
{
    /**
     * @var MailableInterface
     */
    private $blueprint;

    /**
     * @var User
     */
    private $recipient;

    /**
     * @var string|null
     */
    private $batch;

    public function __construct(MailableInterface $blueprint, User $recipient, string $batch = null)
    {
        $this->blueprint = $blueprint;
        $this->recipient = $recipient;
        $this->batch = $batch;
    }

    public function setBatch(string $batch = null)
    {
        $this->batch = $batch;
    }

    public function handle()
    {
        $queued = new QueuedBlueprint();
        $queued->user()->associate($this->recipient);
        // TODO: use SerializesModels? It would reduce the payload size and keep the model up to date
        // but this would also break notifications if the related model gets deleted in the meantime
        $queued->blueprint = serialize($this->blueprint);
        $queued->date = Carbon::now();
        $queued->batch = $this->batch;
        $queued->save();
    }
}
