<?php

namespace Tests\Feature;

use App\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

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

        $this->assertDatabaseHas('videos', [
            'name' => 'fake.mp4',
            'path' => getcwd() . "/$this->path/fake.mp4",
        ]);
    }

    public function testVideoNotFound()
    {
        $this->expectException(\Exception::class);
        $this->artisan($this->command, ['path' => "$this->path/not-found"]);
    }

    public function testDuplicateVideo()
    {
        Video::create(['name' => 'fake-duplicate.mp4', 'hash' => hash('sha256', ''), 'path' => '/fake-duplicate.mp4']);

        $this->artisan($this->command, ['path' => $this->path]);

        $this->assertDatabaseHas('videos', [
            'name' => 'fake-duplicate.mp4',
            'path' => '/fake-duplicate.mp4',
        ]);
        $this->assertDatabaseMissing('videos', [
            'name' => 'fake.mp4',
        ]);
    }
}
