<?php

namespace App\Commands;

use App\Service\FindService;
use App\Service\IndexService;
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

    public function __construct(FindService $findService, IndexService $indexService)
    {
        parent::__construct();

        $this->findService = $findService;
        $this->indexService = $indexService;
        $this->api = new Api(new Client());
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
        $progressBar->start();
        foreach ($videos as $video) {
            $this->createFile($video);
            $progressBar->advance();
        }
        $progressBar->finish();
    }

    protected function createFile(Video $video)
    {
        try {
            $response = $this->api->create(
                config('youku.client_id'),
                config('youku.access_token'),
                $video->name,
                'classical-music',
                '',
                $video->name,
                hash_file('md5', $video->path),
                filesize($video->path),
                null,
                null,
                'reproduced',
                'all',
                null,
                0,
                1,
                0
            );

            $video->upload_token = $response->getUploadToken();
            $video->video_id = $response->getVideoId();
            $video->endpoint = $response->getEndpoint();
            $video->security_token = $response->getSecurityToken();
            $video->oss_bucket = $response->getOssBucket();
            $video->oss_object = $response->getOssObject();
            $video->temp_access_id = $response->getTempAccessId();
            $video->temp_access_secret = $response->getTempAccessSecret();
            $video->expire_time = $response->getExpireTime();
            $video->status = 'created';
            $video->save();
        } catch (UploadException $exception) {
            $this->error(sprintf('File: "%s" created failed, cause by "%s"', $video->name, $exception->getMessage()));
        }
    }

    protected function uploadFiles(Collection $videos)
    {
        $this->info(sprintf('There are %d videos for uploading', $videos->count()));
        $progressBar = $this->output->createProgressBar($videos->count());
        $progressBar->start();
        foreach ($videos as $video) {
            $this->uploadFile($video);
            $progressBar->advance();
        }
        $progressBar->finish();
    }

    protected function uploadFile(Video $video)
    {
        try {
            if ($this->needRefreshOssToken($video)) {
                $video = $this->refreshOssToken($video);
            }

            $ossClient = new OssClient($video->temp_access_id, $video->temp_access_secret, $video->endpoint);
            $ossClient->uploadFile($video->oss_bucket, $video->oss_object, $video->path);

            $video->status = 'uploaded';
            $video->save();
        } catch (OssException $exception) {
            $this->error(sprintf('File: "%s" uploaded failed, cause by "%s"', $video->name, $exception->getMessage()));
        }
    }

    private function needRefreshOssToken(Video $video): bool
    {
        $expiredAt = new \DateTime($video->expire_time);

        return new \DateTime('now') < $expiredAt;
    }

    private function refreshOssToken(Video $video): Video
    {
        /**
         * @var $response StsInf
         */
        $response = $this->api->getStsInf(
            config('youku.client_id'),
            config('youku.access_token'),
            $video->upload_token,
            $video->oss_bucket,
            $video->oss_object
        );

        $video->upload_token = $response->getUploadToken();
        $video->endpoint = $response->getEndpoint();
        $video->security_token = $response->getSecurityToken();
        $video->temp_access_id = $response->getTempAccessId();
        $video->temp_access_secret = $response->getTempAccessSecret();
        $video->expire_time = $response->getExpireTime();
        $video->save();
        return $video;
    }

    protected function commitFiles(Collection $videos)
    {
        $this->info(sprintf('There are %d videos for committing.', $videos->count()));
        $progressBar = $this->output->createProgressBar($videos->count());
        $progressBar->start();
        foreach ($videos as $video) {
            $this->commitFile($video);
            $progressBar->advance();
        }
        $progressBar->finish();
    }

    protected function commitFile(Video $video)
    {
        try {
            $response = $this->api->commit(
                config('youku.access_token'),
                config('youku.client_id'),
                $video->upload_token
            );

            $video->video_id = $response->getVideoId();
            $video->save();
        } catch (UploadException $exception) {
            $this->error(sprintf('File: "%s" committed failed, cause by "%s"', $video->name, $exception->getMessage()));
        }
    }
}
