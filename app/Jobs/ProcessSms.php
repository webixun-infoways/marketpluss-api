<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\AppHelper;
class ProcessSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
	protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		$contact=$this->data['contact'];
		$msg=$this->data['msg'];

        $request_type=$this->data['request_type'];

        if($request_type == "send")
        {
            AppHelper::send_sms($contact,$msg);
        }
        else
        {
            AppHelper::send_sms2($contact,$msg);
        }
    }
}
