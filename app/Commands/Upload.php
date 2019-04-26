<?php

namespace App\Commands;

use App\Service\FindService;
use App\Service\IndexService;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Finder\Finder;

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
     * @var FindService
     */
    protected $findService;

    /**
     * @var IndexService
     */
    protected $indexService;

    public function __construct(FindService $findService, IndexService $indexService)
    {
        parent::__construct();

        $this->findService = $findService;
        $this->indexService = $indexService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $videos = $this->findService->findFiles($this->argument('path'));
        $this->indexService->index($videos);
    }
}
