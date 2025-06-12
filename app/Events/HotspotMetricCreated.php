<?php

namespace App\Events;

use App\Models\HotspotMetric;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HotspotMetricCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $hotspotMetric;

    /**
     * Create a new event instance.
     */
    public function __construct(HotspotMetric $hotspotMetric)
    {
        $this->hotspotMetric = $hotspotMetric;
    }
}
