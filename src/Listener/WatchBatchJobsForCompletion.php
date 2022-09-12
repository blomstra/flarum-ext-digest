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
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;

class WatchBatchJobsForCompletion
{
    protected $aggregator;

    public function __construct(BatchJobAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    /**
     * @param JobProcessed|JobFailed $event
     */
    public function handle($event)
    {
        $this->aggregator->workerFinishedProcessingJob($event->job->getJobId());
    }
}
