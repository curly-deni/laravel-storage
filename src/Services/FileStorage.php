<?php

namespace Aesis\Storage\Services;

use Aesis\Storage\Models\File;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class FileStorage
{
    public function storeContents(
        Model $owner,
        string $key,
        string $contents,
        string $extension,
        ?string $contentType = null,
    ): File {
        return $this->storeContentsOnDisk(
            $owner,
            $key,
            $contents,
            $extension,
            $this->publicDisk(),
            $contentType,
        );
    }

    public function publicDisk(): string
    {
        return (string) (config('storage.public_disk') ?: config('filesystems.public', 'public'));
    }

    public function storeContentsOnDisk(
        Model $owner,
        string $key,
        string $contents,
        string $extension,
        string $disk,
        ?string $contentType = null,
    ): File {
        $options = $this->storageOptions($disk, $contentType);

        return $this->storeUsing(
            $owner,
            $key,
            $extension,
            $disk,
            fn (string $path): bool => Storage::disk($disk)->put($path, $contents, $options),
        );
    }

    /**
     * @param  resource  $stream
     */
    public function storeStreamOnDisk(
        Model $owner,
        string $key,
        $stream,
        string $extension,
        string $disk,
        ?string $contentType = null,
    ): File {
        if (! is_resource($stream)) {
            throw new InvalidArgumentException('File contents must be a stream resource.');
        }

        $options = $this->storageOptions($disk, $contentType);

        return $this->storeUsing(
            $owner,
            $key,
            $extension,
            $disk,
            fn (string $path): bool => Storage::disk($disk)->put($path, $stream, $options),
        );
    }

    public function storeFromDisk(
        Model $owner,
        string $key,
        string $sourceDisk,
        string $sourcePath,
        string $extension,
        string $targetDisk,
        ?string $contentType = null,
    ): File {
        $stream = Storage::disk($sourceDisk)->readStream($sourcePath);

        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to read source file.');
        }

        try {
            return $this->storeStreamOnDisk($owner, $key, $stream, $extension, $targetDisk, $contentType);
        } finally {
            fclose($stream);
        }
    }

    public function deleteStoredObject(File $file): void
    {
        if ($file->path && $file->filesystem !== 'external') {
            if (! Storage::disk($file->filesystem ?: $this->publicDisk())->delete($file->path)) {
                throw new RuntimeException('Unable to delete stored file.');
            }
        }
    }

    /**
     * @param  Closure(string): bool  $write
     */
    private function storeUsing(Model $owner, string $key, string $extension, string $disk, Closure $write): File
    {
        $path = sprintf('%s/%s/%s.%s', $key, $owner->getKey(), Str::uuid(), ltrim($extension, '.'));

        try {
            if (! $write($path)) {
                throw new RuntimeException('Unable to store file.');
            }

            return File::query()->create([
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => $owner->getKey(),
                'key' => $key,
                'path' => $path,
                'filesystem' => $disk,
            ]);
        } catch (Throwable $exception) {
            try {
                if (! Storage::disk($disk)->delete($path)) {
                    report(new RuntimeException('Unable to clean up a stored file after persistence failed.'));
                }
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }
    }

    /** @return array<string, string> */
    private function storageOptions(string $disk, ?string $contentType): array
    {
        $options = $contentType ? ['ContentType' => $contentType] : [];

        if ($disk === $this->publicDisk()) {
            $options['visibility'] = 'public';
        }

        return $options;
    }
}
