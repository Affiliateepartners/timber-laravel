<?php

namespace Liteweb\TimberLaravel;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $body;

    public function __construct(string $body)
    {
        $this->body = $body;
    }

    public function handle(\Liteweb\TimberApi\TimberApi $api)
    {
        $response = $api->sendJsonLogLine($this->body);
    }
}
