<?php

namespace Tests\Feature;

use Tests\TestCase;

class UploadTest extends TestCase
{
    protected $command = 'upload';
    protected $path = 'tests/mock-storage';

    public function testUpload()
    {
        $this->artisan($this->command, ['path' => $this->path])
            ->assertExitCode(0);
    }
}
