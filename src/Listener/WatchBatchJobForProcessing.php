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

use Blomstra\Digest\Batch\BatchJobAggregator;
use Illuminate\Queue\Events\JobProcessing;

class WatchBatchJobForProcessing
{
    protected $aggregator;

    public function __construct(BatchJobAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    public function handle(JobProcessing $event)
    {
        $this->aggregator->workerIsAboutToProcessJob($event->job->getJobId());
    }
}
