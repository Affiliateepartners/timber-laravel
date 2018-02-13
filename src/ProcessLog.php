<?php

namespace Liteweb\Timber\TimberLaravel;

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

    public function handle(\Liteweb\Timber\TimberApi\TimberApi $api)
    {
    	$status = \App\Models\LogStatus::create([
                    'unique_id' => uniqid(),
        ]);

        $status->created = \Carbon\Carbon::now();
        $status->save();

        $response = $api->sendJsonLogLine($this->body);

        $status->delivered = \Carbon\Carbon::now();
        $status->code      = $response->getStatusCode();
        $status->save();
    }
}
