<?php

namespace App\Service;

use App\Contract\UploadService;
use App\Video;
use Chivincent\Youku\Api\Api;
use Chivincent\Youku\Api\Response\StsInf;
use Chivincent\Youku\Exception\UploadException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use OSS\Core\OssException;
use OSS\OssClient;

class OssUploadService implements UploadService
{
    /**
     * @var Api
     */
    protected $api;

    public function __construct()
    {
        $this->api = new Api(new Client());
    }

    public function createFile(Video $video)
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
                config('youku.meta.category', null),
                config('youku.meta.tags', null),
                config('youku.meta.copyright', 'original'),
                config('youku.meta.public_type', 'all'),
                config('youku.meta.watch_password', null),
                0,
                config('youku.oss', false),
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
            Log::error(sprintf('File: "%s"(id: %d) has not been created, it was caused by "%s"', $video->name, $video->id, $exception->getMessage()));
        }
    }

    public function uploadFile(Video $video)
    {
        try {
            if ($this->needRefreshOssToken($video)) {
                $video = $this->refreshOssToken($video);
            }

            $ossClient = new OssClient($video->temp_access_id, $video->temp_access_secret, $video->endpoint);
            $ossClient->uploadFile($video->oss_bucket, $video->oss_object, $video->path);

            $video->status = 'uploaded';
            $video->save();

            $this->commitFile($video);
        } catch (OssException $exception) {
            Log::error(sprintf('File: "%s"(id: %d) has not been uploaded, it was caused by "%s"', $video->name, $video->id, $exception->getMessage()));
        }
    }

    private function needRefreshOssToken(Video $video): bool
    {
        $expiredAt = new \DateTime($video->expire_time);

        return new \DateTime('now') > $expiredAt;
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

    protected function commitFile(Video $video)
    {
        try {
            $response = $this->api->commit(
                config('youku.access_token'),
                config('youku.client_id'),
                $video->upload_token
            );

            $video->video_id = $response->getVideoId();
            $video->status = 'finished';
            $video->save();
        } catch (UploadException $exception) {
            Log::error(sprintf('File: "%s"(id: %d) has not been committed, it was caused by "%s"', $video->name, $video->id, $exception->getMessage()));
        }
    }
}
