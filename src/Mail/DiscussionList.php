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

use Flarum\Discussion\Discussion as FlarumDiscussion;
use Flarum\Mentions\Notification as FlarumMentions;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Subscriptions\Notification as FlarumSubscriptions;
use FoF\FollowTags\Notifications as FoFFollowTags;
use FoF\Subscribed\Blueprints as FoFSubscribed;

/**
 * Manages the list of discussions to render in the digest.
 */
class DiscussionList
{
    /**
     * @var Discussion[]
     */
    public $discussions = [];

    protected function discussion(FlarumDiscussion $flarumDiscussion): Discussion
    {
        if (array_key_exists($flarumDiscussion->id, $this->discussions)) {
            return $this->discussions[$flarumDiscussion->id];
        }

        $discussion = new Discussion($flarumDiscussion);

        $this->discussions[$flarumDiscussion->id] = $discussion;

        return $discussion;
    }

    /**
     * Takes care of an incoming blueprint.
     * If the blueprint can be rendered using our built-in template, true will be returned
     * If the blueprint is unknown or not related to a discussion, false will be returned and the template will use the fallback rendering method.
     *
     * @param BlueprintInterface $blueprint
     *
     * @return bool
     */
    public function handle(BlueprintInterface $blueprint): bool
    {
        if ($blueprint instanceof FlarumSubscriptions\NewPostBlueprint) {
            $this->discussion($blueprint->getSubject())->isFollowed = true;

            return true;
        }

        if ($blueprint instanceof FlarumMentions\PostMentionedBlueprint || $blueprint instanceof FlarumMentions\UserMentionedBlueprint) {
            $this->discussion($blueprint->getSubject()->discussion)->importantPost($blueprint->getSubject()->id)->isMentioned = true;

            return true;
        }

        if ($blueprint instanceof FoFFollowTags\NewDiscussionTagBlueprint) {
            $this->discussion($blueprint->getSubject())->isTagFollowed = true;

            return true;
        }

        if ($blueprint instanceof FoFFollowTags\NewPostBlueprint) {
            $this->discussion($blueprint->getSubject())->isTagLurked = true;

            return true;
        }

        if ($blueprint instanceof FoFSubscribed\DiscussionCreatedBlueprint) {
            $this->discussion($blueprint->getSubject())->isGlobalSubscribed = true;

            return true;
        }

        if ($blueprint instanceof FoFSubscribed\PostCreatedBlueprint) {
            $this->discussion($blueprint->getSubject()->discussion)->importantPost($blueprint->getSubject()->id)->isGlobalSubscribed = true;

            return true;
        }

        return false;
    }
}
