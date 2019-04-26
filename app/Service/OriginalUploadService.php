<?php

namespace App\Service;

use App\Contract\UploadService;
use App\Video;
use Chivincent\Youku\Api\Api;
use Chivincent\Youku\Api\Response\NewSlice;
use Chivincent\Youku\Api\Response\UploadSlice;
use Chivincent\Youku\Exception\UploadException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OriginalUploadService implements UploadService
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
            try {
                $this->api->createFile(
                    gethostbyname($response->getUploadServerUri()),
                    $response->getUploadToken(),
                    filesize($video->path),
                    pathinfo($video->path, PATHINFO_EXTENSION),
                    (int) (config('YOUKU_SLICE_SIZE', 10 * 1024 * 1024) / 1024)
                );
            } catch (\Exception $exception) {
                dd($exception);
            }

            $video->upload_token = $response->getUploadToken();
            $video->upload_server_uri = $response->getUploadServerUri();
            $video->slice_size = config('youku.slice_size', 10 * 1024 * 1024);
            $video->status = 'created';
            $video->save();
        } catch (UploadException $exception) {
            Log::error(sprintf('File: "%s"(id: %d) has not been created, it was caused by "%s"', $video->name, $video->id, $exception->getMessage()));
        }
    }

    public function uploadFile(Video $video)
    {
        $slices = $this->sliceBinary($video->path, $video->slice_size);
        $video->update([
            'status' => 'uploading',
        ]);
        $this->uploadSlices($video, $slices, $video->slice_size);
        $video->update([
            'status' => 'checking',
        ]);
        $this->checkUpload($video);
        $video->update([
            'status' => 'uploaded',
        ]);
    }

    private function sliceBinary(string $path, int $size): array
    {
        $file = fopen($path, 'rb');

        $slices = [];
        $i = 0;
        while ($data = stream_get_contents($file, $size, $size * $i++)) {
            $slices[] = $data;
        }

        fclose($file);

        return $slices;
    }

    private function uploadSlices(Video $video, array $slices, int $size)
    {
        $task = $video->task_id ?? $this->createSliceRoot($video->upload_token, gethostbyname($video->upload_server_uri))->getSliceTaskId();
        $video->update([
            'task_id' => $task,
        ]);

        for ($i = ($video->uploaded_slices ?? 0); $i < count($slices); $i++) {
            $this->uploadCurrentSlice($slices[$i], $video->upload_token, $task++, $size * $i, gethostbyname($video->upload_server_uri));
            $video->update([
                'task_id' => $task,
                'uploaded_slices' => $i,
            ]);
        }
    }

    private function createSliceRoot(string $uploadToken, string $ip): NewSlice
    {
        return $this->api->newSlice($ip, $uploadToken);
    }

    private function uploadCurrentSlice(string $binary, string $uploadToken, string $sliceTaskId, int $offset, string $ip): UploadSlice
    {
        return $this->api->uploadSlice(
            $ip,
            $uploadToken,
            $sliceTaskId,
            $offset,
            strlen($binary),
            $binary,
            dechex(crc32($binary)),
            bin2hex(md5($binary, true))
        );
    }

    private function checkUpload(Video $video)
    {
        do {
            $check = $this->api->check(gethostbyname($video->upload_server_uri), $video->upload_token);
            sleep(config('youku.check_waiting', 10));
        } while(!$check->isFinished() || $check->getStatus() !== 1);
    }

    public function commitFile(Video $video)
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
            Log::error(sprintf('File: "%s"(id: %d) has not been committed, it was caused by "%s"', $video->name, $video->id, $exception->getMessage()));
        }
    }
}
