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
    | The Private Disk
    |--------------------------------------------------------------------------
    |
    | Where school files that must never be web-served are written: documents,
    | materials, submissions, admission attachments, and the finished file a
    | live lesson recording becomes. The disk is named here rather than at each
    | call site so uploads and the routes that hand the files back cannot drift
    | apart, and so a deployment can move the lot by setting one variable.
    |
    | `local` is the classic deployment, where `storage/app/private` persists.
    | Laravel Cloud's filesystem is ephemeral and per-replica: a file written in
    | one request is gone after the next deploy, and a second replica never saw
    | it at all. A Cloud deployment points this at a private bucket instead (the
    | S3 driver below, or Laravel Cloud Object Storage) and nothing in the code
    | has to change — see docs/DEPLOY.md, "Laravel Cloud".
    |
    */

    'private' => env('PRIVATE_DISK', 'local'),

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
         * Scratch space for MediaMTX recordings.
         *
         * The live server writes here (a shared volume in production) and the
         * finalize job copies the finished file onto the private `local` disk,
         * where every other material lives. Keeping it a separate disk means
         * nothing can serve a half-written file: the stream endpoint only ever
         * reads `local`.
         */
        'recordings' => [
            'driver' => 'local',
            'root' => storage_path('app/recordings'),
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            // AWS_DEFAULT_REGION is the SDK's own name; AWS_REGION is what
            // Laravel Cloud injects for an attached bucket. Reading both means
            // a Cloud bucket works with no copied variables, and a plain AWS
            // setup keeps working with the names it already uses.
            'region' => env('AWS_DEFAULT_REGION', env('AWS_REGION')),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            // Likewise AWS_ENDPOINT_URL, which Cloud injects for S3-compatible
            // (R2) buckets and which a plain AWS account never sets.
            'endpoint' => env('AWS_ENDPOINT', env('AWS_ENDPOINT_URL')),
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
