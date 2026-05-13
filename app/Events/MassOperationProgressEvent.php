<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast after each row is processed (or fails) during a bulk operation.
 *
 * Broadcast on a public channel keyed by session ID so each browser tab
 * only receives its own progress updates.
 *
 * Listened to in ManagesMassOperations::handleBatchProgressUpdate() via
 * a dynamic Laravel Echo channel listener.
 */
class MassOperationProgressEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  string  $operationType  'stress' or 'tts'
     * @param  string  $sessionId  Browser session ID, scopes the channel.
     * @param  string  $status  'running' or 'done'
     * @param  int  $processedCount  Rows completed so far (successes + failures).
     * @param  int  $totalCount  Total rows dispatched for this batch.
     * @param  int  $failedCount  Rows that failed (counted within processedCount).
     */
    public function __construct(
        public readonly string $operationType,
        public readonly string $sessionId,
        public readonly string $status,
        public readonly int $processedCount,
        public readonly int $totalCount,
        public readonly int $failedCount,
    ) {}

    /**
     * Broadcast on a public channel namespaced by session ID.
     * Public channel requires no authorization callback.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('mass-op.'.$this->sessionId);
    }

    /**
     * Use a dot-prefixed custom name so Laravel Echo does not prepend the
     * application namespace. The Livewire listener references this as
     * "echo:mass-op.{sessionId},.operation.progress".
     */
    public function broadcastAs(): string
    {
        return 'operation.progress';
    }
}
