<?php

return [
    'client_id' => env('YOUKU_CLIENT_ID'),
    'access_token' => env('YOUKU_ACCESS_TOKEN'),

    'oss' => env('YOUKU_OSS'),

    'slice_size' => env('YOUKU_SLICE_SIZE', 10 * 1024 * 1024), // 10MB

    'meta' => [
        'category' => env('YOUKU_VIDEO_CATEGORY', null),
        'tags' => env('YOUKU_VIDEO_TAGS', 'Other'),
        'copyright' => env('YOUKU_VIDEO_COPYRIGHT', 'original'),
        'public_type' => env('YOUKU_VIDEO_PUBLIC_TYPE', 'all'),
        'watch_passoword' => env('YOUKU_VIDEO_WATCH_PASSWORD', null),
        'deshake' => env('YOUKU_VIDEO_DESHAKE', 0),
    ],
];
