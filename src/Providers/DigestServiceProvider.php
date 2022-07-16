<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Providers;

use Blomstra\Digest\MemoryQueue;
use Flarum\Foundation\AbstractServiceProvider;

class DigestServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton(MemoryQueue::class);
    }
}
