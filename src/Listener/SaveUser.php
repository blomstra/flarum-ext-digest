<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Listener;

use Blomstra\Digest\Mail\SendDigestToUser;
use Flarum\User\Event\Saving;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;
use Illuminate\Validation\Factory;

class SaveUser
{
    protected $validation;

    public function __construct(Factory $validation)
    {
        $this->validation = $validation;
    }

    public function handle(Saving $event)
    {
        $attributes = (array) Arr::get($event->data, 'attributes');

        if (!Arr::exists($attributes, 'digestFrequency')) {
            return;
        }

        $frequency = Arr::get($attributes, 'digestFrequency');

        // If the value didn't change, we don't want to trigger any logic or event below a second time
        if ($frequency === $event->user->digest_frequency) {
            return;
        }

        // Very simple access control, for now you can only edit yourself, just like regular JSON user settings
        if ($event->user->id !== $event->actor->id) {
            throw new PermissionDeniedException();
        }

        $this->validation->make([
            'frequency' => $frequency,
        ], [
            'frequency' => 'nullable|in:daily,weekly',
        ])->validate();

        $event->user->digest_frequency = $frequency;

        // If the user disables digests, send all pending notifications right away
        if (is_null($frequency)) {
            $event->user->afterSave(function (User $user) {
                /**
                 * @var Queue $queue
                 */
                $queue = resolve(Queue::class);

                $queue->push(new SendDigestToUser($user));
            });
        }
    }
}
