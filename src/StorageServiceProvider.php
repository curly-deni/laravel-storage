<?php

namespace Aesis\Storage;

use Aesis\Storage\Filesystem\S3Adapter;
use Aesis\Storage\Services\FileStorage;
use Aesis\Storage\Services\FileUrlResolver;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class StorageServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('storage')
            ->hasConfigFile()
            ->hasMigration('create_storage_files_table');
    }

    public function register(): void
    {
        parent::register();

        $this->app->scoped(FileStorage::class);
        $this->app->scoped(FileUrlResolver::class);
    }

    public function boot(): void
    {
        parent::boot();

        Storage::extend('aesis_s3', function ($app, array $config): FilesystemAdapter {
            $s3 = $app['filesystem']->createS3Driver($config);

            return new S3Adapter(
                $s3->getDriver(),
                $s3->getAdapter(),
                $s3->getConfig(),
                $s3->getClient(),
            );
        });

        if (! FilesystemAdapter::hasMacro('accessUrl')) {
            FilesystemAdapter::macro('accessUrl', function (
                string $path,
                ?\DateTimeInterface $expiration = null,
                array $options = [],
            ): string {
                /** @var FilesystemAdapter $this */
                return app(FileUrlResolver::class)->resolve($this, $path, $expiration, $options);
            });
        }
    }
}
