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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SendDigestCommand extends Command
{
    protected $signature = 'digest:send';
    protected $description = 'Dispatch the send job for all users based on their selected frequency and hour of the day';

    public function handle(Queue $queue, ExtensionManager $extensionManager)
    {
        // For users who have enabled digest for the first time, we'll set the last_digest_sent_at to now
        // so that they don't get a digest immediately.
        User::query()
            ->whereNull('last_digest_sent_at')
            ->whereNotNull('digest_frequency')
            ->update(['last_digest_sent_at' => Carbon::now()]);

        $query = User::query()
            ->whereNotNull('digest_frequency')
            ->where(function (Builder $query) {
                $query
                    ->where(function (Builder $query) {
                        $query
                            ->where('digest_frequency', 'daily')
                            ->where('last_digest_sent_at', '<=', Carbon::now('utc')->subDay());
                    })
                    ->orWhere(function (Builder $query) {
                        $query
                            ->where('digest_frequency', 'weekly')
                            ->where('last_digest_sent_at', '<=', Carbon::now('utc')->subWeek());
                    });
            })
            ->whereRaw('COALESCE(`digest_hour`, 0) = ?', [Carbon::now('utc')->hour]);

        $count = $query->count();

        $this->info("Dispatching jobs for $count users to send a digest...");

        if ($count > 0) {
            $this->output->progressStart();

            $query->each(function (User $user) use ($queue, $extensionManager) {
                $queue->push(new SendDigestToUser($user));

                // For users who enabled Digest before Flarum 1.5 or somehow skipped the frontend code that changes the setting
                // We'll turn it on here to make it apply starting with the next digest
                if ($extensionManager->isEnabled('flarum-subscriptions') && !$user->getPreference('flarum-subscriptions.notify_for_all_posts')) {
                    $user->setPreference('flarum-subscriptions.notify_for_all_posts', true);
                }

                $user->last_digest_sent_at = Carbon::now();
                $user->save();

                $this->output->progressAdvance();
            });

            $this->output->progressFinish();
        }

        $this->info('Done.');
    }
}
