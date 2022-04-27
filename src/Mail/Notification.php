<?php

namespace Blomstra\Digest\Mail;

use Carbon\Carbon;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;

/**
 * A helper class that is passed to the view
 */
class Notification
{
    /**
     * @var BlueprintInterface&MailableInterface
     */
    public $blueprint;

    /**
     * @var Carbon
     */
    public $date;

    public function __construct(BlueprintInterface $blueprint, Carbon $date)
    {
        $this->blueprint = $blueprint;
        $this->date = $date;
    }
}
