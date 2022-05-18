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
use Flarum\User\User;
use Illuminate\Support\Arr;
use Illuminate\View\Factory;

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

    const VIEWS_WITH_GREETINGS = [
        'flarum-mentions::emails.postMentioned',
        'flarum-mentions::emails.userMentioned',
        'flarum-subscriptions::emails.newPost',
    ];

    const VIEW_OVERRIDES = [
        'flarum-subscriptions::emails.newPost' => 'blomstra-digest::emails.newPost',
    ];

    public function __construct(BlueprintInterface $blueprint, Carbon $date)
    {
        $this->blueprint = $blueprint;
        $this->date = $date;
    }

    protected function viewName(): string
    {
        $originalViewName = Arr::get($this->blueprint->getEmailView(), 'text');

        return Arr::get(self::VIEW_OVERRIDES, $originalViewName, $originalViewName);
    }

    /**
     * HTML output for that notification
     */
    public function render(User $user): string
    {
        $viewName = $this->viewName();

        $html = resolve(Factory::class)->make($viewName, [
            'blueprint' => $this->blueprint,
            'user' => $user,
        ])->render();

        // Remove greeting line
        if (in_array($viewName, self::VIEWS_WITH_GREETINGS)) {
            // Remove first 2 lines of string
            $html = explode("\n", $html, 3)[2] ?? '';
        }

        return $html;
    }
}
