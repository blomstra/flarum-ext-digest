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

/**
 * Helper class to define why a post is part of the digest.
 */
class Post
{
    /**
     * @var \Flarum\Post\Post The post model. Will be set by the Discussion helper automatically
     */
    public $post = null;

    /**
     * @var bool Whether that post is part of a mention notification
     */
    public $isMentioned = false;

    /**
     * @var bool Whether this post was notified through fof/subscribed global subscription
     */
    public $isGlobalSubscribed = false;

}
