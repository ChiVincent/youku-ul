<?php

namespace Tests\Feature;

use Tests\TestCase;

class UploadTest extends TestCase
{
    protected $command = 'upload';
    protected $path = 'tests/mock-storage';

    public function setUp(): void
    {
        parent::setUp();
        mkdir("$this->path");
        mkdir("$this->path/not-found");
        touch("$this->path/fake.mp4");
    }

    public function tearDown(): void
    {
        unlink("$this->path/fake.mp4");
        rmdir("$this->path/not-found");
        rmdir("$this->path");
        parent::tearDown();
    }

    public function testUpload()
    {
        $this->artisan($this->command, ['path' => $this->path])
            ->assertExitCode(0);
    }

    public function testVideoNotFound()
    {
        $this->expectException(\Exception::class);
        $this->artisan($this->command, ['path' => "$this->path/not-found"]);
    }
}
