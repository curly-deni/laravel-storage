<?php

use Aesis\Storage\Models\File;
use Aesis\Storage\Tests\Fixtures\Owner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Schema::create('owners', function (Blueprint $table): void {
        $table->id();
    });

    $this->owner = Owner::query()->create();
    Storage::fake('public');
});

it('stores file contents and records their polymorphic owner', function (): void {
    $file = File::storeContents($this->owner, 'avatars', 'image-data', 'png', 'image/png');

    expect($file->exists)->toBeTrue()
        ->and($file->owner_id)->toBe($this->owner->getKey())
        ->and($file->owner_type)->toBe($this->owner->getMorphClass())
        ->and($file->key)->toBe('avatars')
        ->and($file->filesystem)->toBe('public');

    Storage::disk('public')->assertExists($file->path);
});

it('uses the application public disk when no package override is configured', function (): void {
    config()->set('filesystems.public', 's3-public');
    config()->set('storage.public_disk', null);
    Storage::fake('s3-public');

    $file = File::storeContents($this->owner, 'avatars', 'image-data', 'png');

    expect($file->filesystem)->toBe('s3-public');

    Storage::disk('s3-public')->assertExists($file->path);
});

it('deletes the stored object when its record is deleted', function (): void {
    $file = File::storeContents($this->owner, 'avatars', 'image-data', 'png');

    $file->delete();

    Storage::disk('public')->assertMissing($file->path);
});

it('returns external paths as resolved URLs', function (): void {
    $file = File::query()->create([
        'key' => 'documents',
        'path' => 'https://example.test/document.pdf',
        'filesystem' => 'external',
    ]);

    expect($file->resolvedUrl())->toBe('https://example.test/document.pdf');
});

it('uses the public URL for disks with public URL access enabled', function (): void {
    $path = 'avatars/example.png';
    $expectedUrl = Storage::disk('public')->url($path);
    $file = File::query()->create([
        'key' => 'avatars',
        'path' => $path,
        'filesystem' => 'public',
    ]);

    expect(Storage::disk('public')->accessUrl($path))
        ->toBe($expectedUrl)
        ->and($file->resolvedUrl())->toBe($expectedUrl);
});
