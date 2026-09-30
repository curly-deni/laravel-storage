# Laravel Storage

`curly-deni/laravel-storage` adds polymorphic file records and filesystem helpers
to Laravel applications. The package namespace is `Aesis\Storage`.

## Installation

```bash
composer require curly-deni/laravel-storage
php artisan vendor:publish --tag="storage-migrations"
php artisan migrate
```

Publish the configuration when you need to change the defaults:

```bash
php artisan vendor:publish --tag="storage-config"
```

The `storage.table` option defaults to `storage__files`, preserving the table
name used by the application module. Set `storage.public_disk` to override the
default disk. When it is `null`, the package uses `filesystems.public` if your
application defines it, and otherwise uses `public`.

## Store files

Files are associated with an Eloquent model through a nullable polymorphic
`owner` relation. The default `storeContents` method writes to the configured
public disk. Other methods allow selecting a disk or copying a stream/file from
another disk.

```php
use Aesis\Storage\Models\File;

$file = File::storeContents($user, 'avatars', $contents, 'png', 'image/png');
$url = $file->resolvedUrl();

$file->delete(); // Deletes the stored object and its database record.
```

Available storage methods are `storeContents`, `storeContentsOnDisk`,
`storeStreamOnDisk`, and `storeFromDisk`. An `external` filesystem value treats
the file path as an already resolved URL and skips filesystem deletion.

## Filesystem adapter

The package registers the `aesis_s3` filesystem driver. It retains the path
prefix from an S3 endpoint when building URLs, including path-style endpoints
used by S3-compatible services. Disks using the driver keep Laravel's regular
S3 options such as `key`, `secret`, `region`, `bucket`, `url`, `endpoint`, and
`use_path_style_endpoint`.

The source application adds two disk options consumed by the package's
`accessUrl` method:

- `url_access` selects URL generation. `public` calls the disk's `url()` method;
  any other value generates a temporary URL.
- `temporary_url_ttl` sets the default temporary URL lifetime in minutes. It
  defaults to `30` when omitted and is used unless a caller passes an explicit
  expiration time.

The private S3 disk can also set Laravel's `temporary_url` option to replace
the base URL used for signed links. The custom S3 adapter preserves any path
prefix in that URL. This is useful when the S3 API endpoint and the URL exposed
to clients are different.

For example, the source app configures private files for expiring URLs and
public files for stable URLs:

```php
's3-private' => [
    'driver' => 'aesis_s3',
    // Laravel S3 credentials, bucket, endpoint, and path-style options...
    'url_access' => 'temporary',
    'temporary_url_ttl' => 30,
],

's3-public' => [
    'driver' => 'aesis_s3',
    // Laravel S3 credentials, bucket, endpoint, and path-style options...
    'url_access' => 'public',
],
```

## Testing and code style

```bash
composer test
composer analyse
composer format
```

## License

MIT. See [LICENSE.md](LICENSE.md).
