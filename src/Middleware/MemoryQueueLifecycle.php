<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Middleware;

use Blomstra\Digest\MemoryQueue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MemoryQueueLifecycle implements MiddlewareInterface
{
    protected $memoryQueue;

    public function __construct(MemoryQueue $memoryQueue)
    {
        $this->memoryQueue = $memoryQueue;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->memoryQueue->enable();

        $response = $handler->handle($request);

        $this->memoryQueue->send();

        return $response;
    }
}
