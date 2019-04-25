<?php

namespace Tests\Feature;

use Tests\TestCase;

class UploadTest extends TestCase
{
    public function testUpload()
    {
        $this->artisan('upload', ['path' => 'tests/mock-storage'])
            ->assertExitCode(0);
    }
}
