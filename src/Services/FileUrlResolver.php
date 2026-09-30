<?php

namespace Aesis\Storage\Services;

use DateTimeInterface;
use Illuminate\Filesystem\FilesystemAdapter;

final class FileUrlResolver
{
    /** @param array<string, mixed> $options */
    public function resolve(
        FilesystemAdapter $disk,
        string $path,
        ?DateTimeInterface $expiration = null,
        array $options = [],
    ): string {
        $config = $disk->getConfig();

        if (($config['url_access'] ?? 'public') === 'public') {
            return $disk->url($path);
        }

        return $disk->temporaryUrl(
            $path,
            $expiration ?? now()->addMinutes($config['temporary_url_ttl'] ?? 30),
            $options,
        );
    }
}
