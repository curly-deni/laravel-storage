<?php

namespace Aesis\Storage\Filesystem;

use Illuminate\Filesystem\AwsS3V3Adapter;

class S3Adapter extends AwsS3V3Adapter
{
    protected function replaceBaseUrl($uri, $url)
    {
        $parsed = parse_url($url);
        $basePath = rtrim((string) ($parsed['path'] ?? ''), '/');

        if ($basePath !== '') {
            $uri = $uri->withPath($basePath.'/'.ltrim($uri->getPath(), '/'));
        }

        if (isset($parsed['host'])) {
            return parent::replaceBaseUrl($uri, $url);
        }

        return $uri
            ->withScheme('')
            ->withHost('')
            ->withPort(null);
    }
}
