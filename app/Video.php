<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'name', 'hash', 'video_id', 'upload_server_uri', 'endpoint', 'security_token', 'oss_bucket', 'oss_object',
        'temp_access_id', 'temp_access_secret', 'expire_time', 'slice_size', 'uploaded_slices',
    ];
}
