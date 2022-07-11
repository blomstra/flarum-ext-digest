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
 *
 * @property \Flarum\Post\Post $post        The post model. Will be set by the Discussion helper automatically
 * @property bool              $isMentioned Whether that post is part of a mention notification
 */
class Post
{
    public $post = null;
    public $isMentioned = false;
}
