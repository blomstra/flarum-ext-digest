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
use Flarum\Locale\Translator;
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

    const VIEWS_WITH_GREETINGS = [];

    const VIEW_OVERRIDES = [];

    const BLUEPRINT_REMOVE_SUBJECT = [];

    public function __construct(BlueprintInterface $blueprint, Carbon $date)
    {
        $this->blueprint = $blueprint;
        $this->date = $date;
    }

    /**
     * Returns the view to use as an array where [0] is text or html and [1] is the view name.
     */
    protected function viewName(): array
    {
        foreach (['html', 'text'] as $mode) {
            $originalViewName = Arr::get($this->blueprint->getEmailView(), $mode);

            if (!$originalViewName) {
                continue;
            }

            $override = Arr::get(self::VIEW_OVERRIDES, $originalViewName);

            if ($override) {
                return ['html', $override];
            }

            return [$mode, $originalViewName];
        }

        throw new \Exception('Could not find an email view for '.get_class($this->blueprint));
    }

    /**
     * HTML output for that notification.
     */
    public function render(User $user): string
    {
        $viewName = $this->viewName();

        $html = resolve(Factory::class)->make($viewName[1], [
            'blueprint' => $this->blueprint,
            'user'      => $user,
        ])->render();

        // If an original text view is used verbatim, we must escape the HTML to prevent any HTML injection
        if ($viewName[0] === 'text') {
            $html = e($html);
        }

        // Remove greeting line
        if (in_array($viewName[1], self::VIEWS_WITH_GREETINGS)) {
            // Remove first 2 lines of string
            $html = explode("\n", $html, 3)[2] ?? '';
        }

        return $html;
    }

    public function title(Translator $translator): string
    {
        $title = $this->date->format('Y-m-d H:i');

        if (!in_array(get_class($this->blueprint), self::BLUEPRINT_REMOVE_SUBJECT)) {
            $title .= ' - '.$this->blueprint->getEmailSubject($translator);
        }

        return $title;
    }
}
