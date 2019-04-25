<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class Upload extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'upload {path}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Upload video(s) from path to youku.com.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
    }
}
