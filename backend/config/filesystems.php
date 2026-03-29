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
    | IMPORTANT: Default should ALWAYS be 'local' to avoid S3 initialization
    | during bootstrap. Use specific disks (public_assets, private_assets) 
    | explicitly in code.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Drive Files Disk
    |--------------------------------------------------------------------------
    |
    | Dedicated disk used by the personal drive feature. Keep this separate
    | from the global default so avatars/other files can remain local while
    | drive uploads use private S3 storage in production.
    |
    */
    'drive_disk' => env('DRIVE_FILESYSTEM_DISK', env('FILESYSTEM_DISK', 'local')),

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

        /*
        |--------------------------------------------------------------------------
        | Local Disk (Default)
        |--------------------------------------------------------------------------
        | Used for temporary files, cache, and as fallback.
        | NOT for public assets or branding.
        */
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Public Disk (Local Development Only)
        |--------------------------------------------------------------------------
        | For local development when S3 is not available.
        | Uses storage/app/public with symlink to public/storage.
        */
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Credentials Disk (Private Documents)
        |--------------------------------------------------------------------------
        | For storing sensitive credential documents (licenses, certifications).
        | These should NEVER be publicly accessible.
        */
        'credentials' => [
            'driver' => 'local',
            'root' => storage_path('app/credentials'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Public Assets Disk (S3 + CloudFront)
        |--------------------------------------------------------------------------
        | For publicly accessible assets like branding logos, public images.
        | Files stored here get a CloudFront URL for fast CDN delivery.
        | 
        | Usage: Storage::disk('public_assets')->put('branding/...', $file)
        | URL: https://cdn.agenchq.com/branding/...
        |
        | IMPORTANT: Store the FULL URL in the database, not the path.
        */
        'public_assets' => [
            'driver' => 's3',
            // No explicit key/secret - use default credential chain (ECS task role)
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_PUBLIC_BUCKET'),
            'url' => env('AWS_PUBLIC_URL'), // CloudFront URL: https://cdn.agenchq.com
            'visibility' => 'private', // Don't set ACLs - bucket policy handles public access
            'throw' => true, // Enable to catch S3 errors
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Private Assets Disk (S3 Private)
        |--------------------------------------------------------------------------
        | For private assets that require signed URLs or controlled access.
        | Documents, sensitive files, etc.
        |
        | Usage: Storage::disk('private_assets')->put('documents/...', $file)
        | Access via signed URLs generated by the application.
        */
        'private_assets' => [
            'driver' => 's3',
            // No explicit key/secret - use default credential chain (ECS task role)
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_PRIVATE_BUCKET'),
            'visibility' => 'private',
            'throw' => true, // Enable to catch S3 errors
            'report' => false,
            'options' => [
                'ACL' => null, // Disable ACLs - bucket uses bucket policy instead
            ],
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
