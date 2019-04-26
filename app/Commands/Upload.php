<?php

namespace App\Commands;

use App\Contract\UploadService;
use App\Service\FindService;
use App\Service\IndexService;
use App\Service\OssUploadService;
use App\Video;
use Chivincent\Youku\Api\Api;
use Chivincent\Youku\Api\Response\StsInf;
use Chivincent\Youku\Exception\UploadException;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use LaravelZero\Framework\Commands\Command;
use OSS\Core\OssException;
use OSS\OssClient;

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

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var UploadService
     */
    protected $uploadService;

    public function __construct(FindService $findService, IndexService $indexService)
    {
        parent::__construct();

        $this->findService = $findService;
        $this->indexService = $indexService;
        $this->api = new Api(new Client());

        $this->uploadService = config('youku.oss', false)
            ? new OssUploadService()
            : new \stdClass();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->indexing();

        $this->createFiles(Video::uncreatedFiles());

        $this->uploadFiles(Video::unuploadedFiles());

        $this->commitFiles(Video::uncommittedFiles());
    }

    protected function indexing()
    {
        $this->indexService->index($this->findService->findFiles($this->argument('path')));
    }

    protected function createFiles(Collection $videos)
    {
        $this->info(sprintf('There are %d videos for creating.', $videos->count()));
        $progressBar = $this->output->createProgressBar($videos->count());
        $progressBar->setMessage('File creating...');
        $progressBar->start();
        foreach ($videos as $video) {
            $this->uploadService->createFile($video);
            $progressBar->advance();
        }
        $progressBar->finish();
    }

    protected function uploadFiles(Collection $videos)
    {
        $this->info(sprintf('There are %d videos for uploading', $videos->count()));
        $progressBar = $this->output->createProgressBar($videos->count());
        $progressBar->start();
        foreach ($videos as $video) {
            $this->uploadService->uploadFile($video);
            $progressBar->advance();
        }
        $progressBar->finish();
    }

    protected function commitFiles(Collection $videos)
    {
        $this->info(sprintf('There are %d videos for committing.', $videos->count()));
        $progressBar = $this->output->createProgressBar($videos->count());
        $progressBar->start();
        foreach ($videos as $video) {
            $this->uploadService->commitFile($video);
            $progressBar->advance();
        }
        $progressBar->finish();
    }
}
