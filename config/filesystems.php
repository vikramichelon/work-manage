<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
         * 'public' disk — where user-uploaded images / attachments live.
         *
         * By default points to storage/app/public (standard Laravel layout).
         *
         * On servers using the "split deployment" pattern (e.g. Cloudways
         * shared hosting where public_html/ is the web root and the Laravel
         * app sits inside public_html/work-manage/), set PUBLIC_DISK_ROOT
         * in .env so uploads land in a web-reachable folder:
         *
         *     PUBLIC_DISK_ROOT=../storage
         *
         * That resolves to public_html/storage/ — directly visible at the
         * URL /storage/... without needing the standard `storage:link`.
         */
        'public' => [
            'driver' => 'local',
            'root' => (function () {
                $custom = env('PUBLIC_DISK_ROOT');
                if (! $custom) {
                    return storage_path('app/public');
                }
                // Absolute path (unix /... or windows C:\...) → use as-is.
                // Otherwise treat as relative to the project base.
                return (str_starts_with($custom, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $custom))
                    ? $custom
                    : base_path($custom);
            })(),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
