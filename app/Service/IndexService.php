<?php

namespace App\Service;

use App\Video;
use Symfony\Component\Finder\Finder;

class IndexService
{
    public function index(Finder $videos)
    {
        foreach ($videos as $video) {
            if (Video::where('hash', $hash = hash_file('sha256', $video->getRealPath()))->exists()) {
                continue;
            }

            Video::create([
                'name' => $video->getFilename(),
                'hash' => $hash,
                'path' => $video->getRealPath(),
            ]);
        }
    }
}
