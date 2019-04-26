<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'name', 'hash', 'path', 'upload_token', 'video_id', 'upload_server_uri', 'endpoint', 'security_token',
        'oss_bucket', 'oss_object', 'temp_access_id', 'temp_access_secret', 'expire_time', 'slice_size',
        'task_id', 'uploaded_slices', 'status',
    ];

    public static function uncreatedFiles(): Collection
    {
        return self::where('status', 'pending')->get();
    }

    public static function unuploadedFiles(): Collection
    {
        return self::where('status', 'created')->orWhere('status', 'uploading')->get();
    }

    public static function uncommittedFiles(): Collection
    {
        return self::where('status', 'uploaded')->get();
    }
}
