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
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Message;
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

        $mailer->send(
            [
                'text' => 'blomstra-digest::emails.digest',
            ],
            [
                'notifications' => $queuedBlueprints->map(function (QueuedBlueprint $queuedBlueprint) {
                    return new Notification(unserialize($queuedBlueprint->blueprint), $queuedBlueprint->date);
                }),
                'user' => $this->user,
            ],
            function (Message $message) use ($translator) {
                $message->to($this->user->email, $this->user->display_name)
                    ->subject($translator->trans('blomstra-digest.email.subject'));
            }
        );

        // Now that we are done, we can delete all queued blueprints that were just sent
        QueuedBlueprint::query()
            ->where('user_id', $this->user->id)
            ->where('date', '<', $processingNow)
            ->delete();
    }
}
