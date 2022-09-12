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

use Blomstra\Digest\Batch\BatchJobAggregator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SingleDigestBatchLifecycle implements MiddlewareInterface
{
    protected $aggregator;

    public function __construct(BatchJobAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->aggregator->startAggregating();

        $response = $handler->handle($request);

        $this->aggregator->stopAggregating();

        return $response;
    }
}
