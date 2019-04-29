<?php

namespace App\Contract;

use App\Video;

interface UploadService
{
    public function createFile(Video $video);

    public function uploadFile(Video $video);
}
