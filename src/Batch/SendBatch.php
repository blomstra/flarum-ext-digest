<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Batch;

use Blomstra\Digest\Mail\SendDigestToUser;
use Blomstra\Digest\QueuedBlueprint;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;

class SendBatch extends AbstractJob
{
    protected $batchId;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
    }

    public function handle()
    {
        $userIds = QueuedBlueprint::query()->where('batch', $this->batchId)->groupBy('user_id')->pluck('user_id');

        User::query()->whereIn('id', $userIds)->each(function (User $user) {
            resolve(Queue::class)->push(new SendDigestToUser($user, $this->batchId));
        });
    }
}
