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

use Blomstra\Digest\Mail\DiscussionList;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Arr;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendSingleDigestJob extends AbstractJob
{
    /**
     * @var Discussion|Post
     */
    protected $subject;

    /**
     * @var MailableInterface[]
     */
    protected $blueprints;

    /**
     * @var User
     */
    protected $recipient;

    /**
     * @param Discussion|Post      $subject
     * @param BlueprintInterface[] $blueprints
     * @param User                 $recipient
     */
    public function __construct(AbstractModel $subject, array $blueprints, User $recipient)
    {
        $this->subject = $subject;
        $this->blueprints = $blueprints;
        $this->recipient = $recipient;
    }

    public function handle(Mailer $mailer, TranslatorInterface $translator)
    {
        $discussions = new DiscussionList();

        foreach ($this->blueprints as $blueprint) {
            if (!$discussions->handle($blueprint)) {
                // TODO: handle more gracefully
                // This should be done in in the memory queue itself to be able to send those blueprints with the regular Flarum job
                throw new \Exception('The non-digest grouping is currently not compatible with notifications of type '.$blueprint::getType());
            }
        }

        // This shouldn't usually happen, but it happened in development so best to have this error instead of a cryptic one from the view
        if (count($discussions->discussions) === 0) {
            throw new \Exception('The list of blueprints could not be converted to a discussion digest');
        }

        $mailer->send(
            [
                'html' => 'blomstra-digest::emails.single',
            ],
            [
                'discussion' => Arr::first($discussions->discussions), // Must use first() because array is keyed by discussion ID
                'user'       => $this->recipient,
            ],
            function (Message $message) use ($translator) {
                $isDiscussion = $this->subject instanceof Discussion;

                $message->to($this->recipient->email, $this->recipient->display_name)
                    ->subject($translator->trans('blomstra-digest.email.'.($isDiscussion ? 'discussion' : 'post').'.subject', [
                        'title' => $isDiscussion ? $this->subject->title : $this->subject->discussion->title,
                    ]));
            }
        );
    }
}
