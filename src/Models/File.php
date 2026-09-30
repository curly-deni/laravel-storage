<?php

namespace Aesis\Storage\Models;

use Aesis\Storage\Services\FileStorage;
use Aesis\Storage\Services\FileUrlResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int|string|null $owner_id
 * @property string $key
 * @property string|null $path
 * @property string $filesystem
 * @property Carbon|null $expires_at
 */
class File extends Model
{
    protected $table = 'storage__files';

    public const UPDATED_AT = null;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'key',
        'path',
        'filesystem',
        'expires_at',
    ];

    public function getTable(): string
    {
        return (string) config('storage.table', parent::getTable());
    }

    protected static function booted(): void
    {
        static::deleting(function (self $file): void {
            $file->deleteStoredObject();
        });
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function resolvedUrl(): ?string
    {
        if (! $this->path) {
            return null;
        }

        if ($this->filesystem === 'external') {
            return $this->path;
        }

        $disk = Storage::disk($this->filesystem ?: app(FileStorage::class)->publicDisk());

        return app(FileUrlResolver::class)->resolve($disk, $this->path);
    }

    public static function storeContents(
        Model $owner,
        string $key,
        string $contents,
        string $extension,
        ?string $contentType = null,
    ): self {
        return app(FileStorage::class)->storeContents($owner, $key, $contents, $extension, $contentType);
    }

    public static function storeContentsOnDisk(
        Model $owner,
        string $key,
        string $contents,
        string $extension,
        string $disk,
        ?string $contentType = null,
    ): self {
        return app(FileStorage::class)->storeContentsOnDisk($owner, $key, $contents, $extension, $disk, $contentType);
    }

    /**
     * @param  resource  $stream
     */
    public static function storeStreamOnDisk(
        Model $owner,
        string $key,
        $stream,
        string $extension,
        string $disk,
        ?string $contentType = null,
    ): self {
        return app(FileStorage::class)->storeStreamOnDisk($owner, $key, $stream, $extension, $disk, $contentType);
    }

    public static function storeFromDisk(
        Model $owner,
        string $key,
        string $sourceDisk,
        string $sourcePath,
        string $extension,
        string $targetDisk,
        ?string $contentType = null,
    ): self {
        return app(FileStorage::class)->storeFromDisk(
            $owner,
            $key,
            $sourceDisk,
            $sourcePath,
            $extension,
            $targetDisk,
            $contentType,
        );
    }

    public function deleteStoredObject(): void
    {
        app(FileStorage::class)->deleteStoredObject($this);
    }
}
