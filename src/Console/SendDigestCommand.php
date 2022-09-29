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
use Flarum\Extension\ExtensionManager;
use Flarum\User\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Queue;

class SendDigestCommand extends Command
{
    protected $signature = 'digest:send {frequency}';

    protected $description = 'Dispatch the send job for all users that have selected the given frequency';

    public function handle(Queue $queue, ExtensionManager $extensionManager)
    {
        $frequency = $this->argument('frequency');

        $query = User::query()->where('digest_frequency', $frequency);

        $count = $query->count();

        $this->info("Dispatching jobs for $count users with frequency setting $frequency");

        if ($count > 0) {
            $this->output->progressStart();

            $query->each(function (User $user) use ($queue, $extensionManager) {
                $queue->push(new SendDigestToUser($user));

                // For users who enabled Digest before Flarum 1.5 or somehow skipped the frontend code that changes the setting
                // We'll turn it on here to make it apply starting with the next digest
                if ($extensionManager->isEnabled('flarum-subscriptions') && !$user->getPreference('flarum-subscriptions.notify_for_all_posts')) {
                    $user->setPreference('flarum-subscriptions.notify_for_all_posts', true);
                    $user->save();
                }

                $this->output->progressAdvance();
            });

            $this->output->progressFinish();
        }

        $this->info('Done.');
    }
}
