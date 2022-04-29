<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Console;

use Blomstra\Digest\Mail\SendDigestToUser;
use Flarum\User\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Queue;

class SendDigestCommand extends Command
{
    protected $signature = 'digest:send {frequency}';

    protected $description = 'Dispatch the send job for all users that have selected the given frequency';

    public function handle(Queue $queue)
    {
        $frequency = $this->argument('frequency');

        $query = User::query()->where('digest_frequency', $frequency);

        $count = $query->count();

        $this->info("Dispatching jobs for $count users with frequency setting $frequency");

        if ($count > 0) {
            $this->output->progressStart();

            $query->each(function (User $user) use ($queue) {
                $queue->push(new SendDigestToUser($user));

                $this->output->progressAdvance();
            });

            $this->output->progressFinish();
        }

        $this->info('Done.');
    }
}
