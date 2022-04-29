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

use Carbon\Carbon;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;

/**
 * A helper class that is passed to the view.
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
