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
use Flarum\Discussion\Discussion;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Mail\Message;
use Illuminate\Support\Arr;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendDigestToUser extends AbstractJob
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle(Mailer $mailer, TranslatorInterface $translator)
    {
        // Use a specific date to retrieve and subsequently delete queued blueprints, that way any new notification
        // that might be queued while this job is running won't be deleted and can be sent in the next batch
        $processingNow = Carbon::now();

        /**
         * @var Collection|QueuedBlueprint[] $queuedBlueprints
         */
        $queuedBlueprints = QueuedBlueprint::query()
            ->where('user_id', $this->user->id)
            ->where('date', '<', $processingNow)
            ->orderBy('date', 'asc')
            ->get();

        // If there's nothing queued, don't send any mail
        if (count($queuedBlueprints) === 0) {
            return;
        }

        $discussions = [];

        $discussionCount = 0;
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

            $discussion = null;

            if ($model instanceof Discussion) {
                $discussion = $model;
            } elseif ($model instanceof Post) {
                $discussion = $model->discussion;
            }

            $discussionId = $discussion ? $discussion->id : 0;

            if (!Arr::exists($discussions, $discussionId)) {
                // TODO: place "other" key at the end of the array
                $discussions[$discussionId] = new Group($discussion);

                if ($discussionId) {
                    $discussionCount++;
                }
            }

            $discussions[$discussionId]->notifications[] = new Notification($blueprint, $queuedBlueprint->date);

            $notificationCount++;
        }

        $mailer->send(
            [
                'html' => 'blomstra-digest::emails.digest',
            ],
            [
                'discussionCount'      => $discussionCount,
                'notificationCount'    => $notificationCount,
                'groupedNotifications' => $discussions,
                'user'                 => $this->user,
            ],
            function (Message $message) use ($translator) {
                $message->to($this->user->email, $this->user->display_name)
                    ->subject($translator->trans('blomstra-digest.email.digest.subject'));
            }
        );

        // Now that we are done, we can delete all queued blueprints that were just sent
        QueuedBlueprint::query()
            ->where('user_id', $this->user->id)
            ->where('date', '<', $processingNow)
            ->delete();
    }
}
