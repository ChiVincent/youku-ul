<?php

namespace App\Service;

use Exception;
use Symfony\Component\Finder\Finder;

class FindService
{
    // Support formats, source: https://csc.youku.com/feedback-web/help/index.html?style=1&loreid=1928
    protected $formats = [
        // Microsoft
        '*.wmv', '*.avi', /*'.dat',*/ '*.asf',
        // Real Player
        '*.rm', '*.rmvb', '*.ram',
        // MPEG
        '*.mpg', '*.mpeg',
        // Mobile
        '*.3gp',
        // Apple
        '*.mov',
        // Sony
        '*.mp4', '*.m4v',
        // DV
        '*.dvix', '*.dv',
        // Others
        '*.mkv', '*.flv', '*.vob', '*.qt', '*.divx', '*.cpk', '*.fli', '*.flc', '*.mod'
    ];

    public function findFiles(string $path): Finder
    {
        $finder = new Finder();
        $finder->files()->name($this->formats)->in($path);

        if (!$finder->hasResults()) {
            throw new Exception("Cannot find any videos in `$path`");
        }

        return $finder;
    }
}
