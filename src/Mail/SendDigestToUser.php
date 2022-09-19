<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Mail;

use Blomstra\Digest\QueuedBlueprint;
use Carbon\Carbon;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Mail\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendDigestToUser extends AbstractJob
{
    protected $user;
    protected $batch;

    public function __construct(User $user, string $batch = null)
    {
        $this->user = $user;
        $this->batch = $batch;
    }

    protected function blueprintQuery(Carbon $processingNow): Builder
    {
        $query = QueuedBlueprint::query()->where('user_id', $this->user->id);

        if ($this->batch) {
            return $query->where('batch', $this->batch);
        } else {
            return $query->whereNull('batch')->where('date', '<', $processingNow);
        }
    }

    public function handle(Mailer $mailer, TranslatorInterface $translator)
    {
        // Use a specific date to retrieve and subsequently delete queued blueprints, that way any new notification
        // that might be queued while this job is running won't be deleted and can be sent in the next batch
        $processingNow = Carbon::now();

        /**
         * @var Collection|QueuedBlueprint[] $queuedBlueprints
         */
        $queuedBlueprints = $this->blueprintQuery($processingNow)->orderBy('date', 'asc')->get();

        // If there's nothing queued, don't send any mail
        if (count($queuedBlueprints) === 0) {
            return;
        }

        $discussions = new DiscussionList();

        /**
         * @var Notification $otherNotifications
         */
        $otherNotifications = [];

        $notificationCount = 0;

        foreach ($queuedBlueprints as $queuedBlueprint) {
            /**
             * @var BlueprintInterface $blueprint
             */
            $blueprint = unserialize($queuedBlueprint->blueprint);

            if (!($blueprint instanceof BlueprintInterface)) {
                resolve('log')->info('blomstra-digest: Unserialized blueprint is not an instance of BlueprintInterface');
                continue;
            }

            $model = $blueprint->getSubject();

            try {
                // Retrieve an updated version of the model
                // This allows us to ignore notifications that might be for deleted models
                // And avoids any error when trying to retrieve relationships off that model later
                $model->refresh();
            } catch (ModelNotFoundException $exception) {
                continue;
            }

            if (!$discussions->handle($blueprint)) {
                $otherNotifications[] = new Notification($blueprint, $queuedBlueprint->date);
            }

            $notificationCount++;
        }

        $mailer->send(
            [
                'html' => 'blomstra-digest::emails.digest',
            ],
            [
                'notificationCount'  => $notificationCount,
                'discussions'        => $discussions->discussions,
                'otherNotifications' => $otherNotifications,
                'user'               => $this->user,
            ],
            function (Message $message) use ($translator) {
                $message->to($this->user->email, $this->user->display_name)
                    ->subject($translator->trans('blomstra-digest.email.digest.subject'));
            }
        );

        // If this was a scheduled digest, store the date to the user model
        if (!$this->batch) {
            $this->user->last_digest_sent_at = $processingNow;
            $this->user->save();
        }

        // Now that we are done, we can delete all queued blueprints that were just sent
        $this->blueprintQuery($processingNow)->delete();
    }
}
