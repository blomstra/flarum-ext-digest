<?php

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

    public function __construct(MailableInterface $blueprint, User $recipient)
    {
        $this->blueprint = $blueprint;
        $this->recipient = $recipient;
    }

    public function handle()
    {
        $queued = new QueuedBlueprint();
        $queued->user()->associate($this->recipient);
        // TODO: use SerializesModels? It would reduce the payload size and keep the model up to date
        // but this would also break notifications if the related model gets deleted in the meantime
        $queued->blueprint = serialize($this->blueprint);
        $queued->date = Carbon::now();
        $queued->save();
    }
}
