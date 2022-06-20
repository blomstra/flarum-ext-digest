<?php

namespace Blomstra\Digest\Mail;

use Flarum\Discussion\Discussion as FlarumDiscussion;
use Flarum\Notification\Blueprint\BlueprintInterface;

/**
 * Manages the list of discussions to render in the digest
 */
class DiscussionList
{
    /**
     * @var Discussion[] $discussions
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
     * If the blueprint is unknown or not related to a discussion, false will be returned and the template will use the fallback rendering method
     * @param BlueprintInterface $blueprint
     * @return bool
     */
    public function handle(BlueprintInterface $blueprint): bool
    {
        // From flarum/subscriptions
        if ($blueprint::getType() === 'newPost') {
            $this->discussion($blueprint->getSubject())->isFollowed = true;

            return true;
        }

        // From flarum/mentions
        if ($blueprint::getType() === 'postMentioned' || $blueprint::getType() === 'userMentioned') {
            $this->discussion($blueprint->getSubject()->discussion)->importantPost($blueprint->getSubject()->id)->isMentioned = true;

            return true;
        }

        // From fof/follow-tags
        if ($blueprint::getType() === 'newDiscussionInTag') {
            $this->discussion($blueprint->getSubject())->isTagFollowed = true;

            return true;
        }

        // TODO: fof/follow-tags's NewDiscussionTagBlueprint could be handled in our template in the future. Currently isn't

        // From fof/follow-tags
        if ($blueprint::getType() === 'newPostInTag') {
            $this->discussion($blueprint->getSubject()->discussion)->isTagLurked = true;

            return true;
        }

        return false;
    }
}
