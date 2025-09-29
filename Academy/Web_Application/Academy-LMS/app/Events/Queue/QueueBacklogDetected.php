<?php

namespace App\Events\Queue;

use App\Models\QueueMetric;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueBacklogDetected
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<int, string> $alerts
     */
    public function __construct(public readonly QueueMetric $metric, public readonly array $alerts)
    {
    }
}
