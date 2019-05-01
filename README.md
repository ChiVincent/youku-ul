# Youku-UL

Youku Uploader for Command Line Interface. 

## Usage

```bash
$ export YOUKU_CLIENT_ID=
$ export YOUKU_ACCESS_TOKEN=
$ touch /tmp/youku-ul.sqlite
$ export DB_DATABASE=/tmp/youku-ul.sqlite
$ # export YOUKU_VIDEO_TAGS=Other
$ # export YOUKU_VIDEO_COPYRIGHT=original
$ # export YOUKU_VIDEO_PUBLIC_TYPE=all
$ php youku-ul migrate
$ php youku-ul upload {your_storage_path_for_videos}
```

## Note

1. OSS Upload Method is not implemented by youku.com.
2. Please create empty file in `DB_DATABASE` path. 

## Credit

1. [Laravel Zero](https://github.com/laravel-zero/laravel-zero)
