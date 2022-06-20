<?php

namespace Blomstra\Digest\Mail;

/**
 * Helper class to define why a post is part of the digest
 *
 * @property \Flarum\Post\Post $post The post model. Will be set by the Discussion helper automatically
 * @property bool $isMentioned Whether that post is part of a mention notification
 */
class Post
{
    public $post = null;
    public $isMentioned = false;
}
