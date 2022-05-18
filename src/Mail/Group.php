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

use Flarum\Discussion\Discussion;

/**
 * A helper class that is passed to the view.
 */
class Group
{
    /**
     * @var Discussion|null
     */
    public $discussion;

    /**
     * @var Notification[]
     */
    public $notifications = [];

    public function __construct(Discussion $discussion = null)
    {
        $this->discussion = $discussion;
    }
}
