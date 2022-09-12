<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Batch;

use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;

class DigestBatchRepository
{
    protected $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Adds the given job-batch association to the database
     * @param string $jobId
     * @param string $batchId
     */
    public function addJob(string $jobId, string $batchId): void
    {
        $this->db->table('digest_batches')->insert([
            'job_id' => $jobId,
            'batch_id' => $batchId,
            'queued_at' => Carbon::now(),
        ]);
    }

    /**
     * Retrieves the batch ID for a given job ID. This also acts as an existence check
     * @param string $jobId
     * @return string|null Batch ID
     */
    public function getJobBatch(string $jobId): ?string
    {
        $record = $this->db->table('digest_batches')->where('job_id', $jobId)->first();

        if (!$record) {
            return null;
        }

        return $record->batch_id;
    }

    /**
     * Checks if there are any remaining jobs for a given batch
     * @param string $batchId
     * @return bool
     */
    public function hasJobsLeft(string $batchId): bool
    {
        return $this->db->table('digest_batches')->where('batch_id', $batchId)->exists();
    }

    /**
     * Remove the given job ID from the database
     * @param string $jobId
     */
    public function removeJob(string $jobId): void
    {
        $this->db->table('digest_batches')->where('job_id', $jobId)->delete();
    }
}
