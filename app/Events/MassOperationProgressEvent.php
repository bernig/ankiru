<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast after each row is processed (or fails) during a bulk operation.
 *
 * Broadcast on the owner's private channel (App.Models.User.{id}). The
 * payload carries the batch UUID so each editor tab only applies the
 * progress of the batch it is tracking.
 *
 * Listened to in ManagesMassOperations::handleBatchProgressUpdate() via
 * a dynamic Laravel Echo channel listener.
 */
class MassOperationProgressEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  string  $operationType  'stress' or 'tts'
     * @param  string  $batchId  Per-batch UUID, identifies the batch in the payload.
     * @param  int  $userId  Owner of the batch, scopes the private channel.
     * @param  string  $status  'running' or 'done'
     * @param  int  $processedCount  Rows completed so far (successes + failures).
     * @param  int  $totalCount  Total rows dispatched for this batch.
     * @param  int  $failedCount  Rows that failed (counted within processedCount).
     */
    public function __construct(
        public readonly string $operationType,
        public readonly string $batchId,
        public readonly int $userId,
        public readonly string $status,
        public readonly int $processedCount,
        public readonly int $totalCount,
        public readonly int $failedCount,
    ) {}

    /**
     * Authorized by the App.Models.User.{id} callback in routes/channels.php.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('App.Models.User.'.$this->userId);
    }

    /**
     * @return array{operationType: string, batchId: string, status: string, processedCount: int, totalCount: int, failedCount: int}
     */
    public function broadcastWith(): array
    {
        return [
            'operationType' => $this->operationType,
            'batchId' => $this->batchId,
            'status' => $this->status,
            'processedCount' => $this->processedCount,
            'totalCount' => $this->totalCount,
            'failedCount' => $this->failedCount,
        ];
    }

    /**
     * Use a dot-prefixed custom name so Laravel Echo does not prepend the
     * application namespace. The Livewire listener references this as
     * "echo-private:App.Models.User.{id},.operation.progress".
     */
    public function broadcastAs(): string
    {
        return 'operation.progress';
    }
}
