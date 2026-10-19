<?php

namespace App\Services\Media;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class FileUploadService
{
    public function __construct(
        private readonly ImageResizeService $imageResizeService
    ) {}

    /**
     * Stores a file and returns its path relative to the upload disk
     * (directory/uuid.ext). Pass an image $preset (see config('media.presets'))
     * to resize and re-encode it first; resizing is skipped when
     * media.resize_enabled is false.
     *
     * Old files are never removed here: call delete() after the DB transaction commits.
     *
     * @throws InvalidArgumentException when the preset does not exist
     * @throws RuntimeException when the file cannot be written
     */
    public function upload(UploadedFile $file, string $directory, ?string $preset = null): string
    {
        $directory = trim($directory, '/');
        $resize = $preset !== null && config('media.resize_enabled');

        $extension = $resize ? $this->imageResizeService->extensionFor($preset) : $file->extension();
        $path = $directory.'/'.Str::uuid()->toString().'.'.$extension;

        $stored = $resize
            ? $this->disk()->put($path, $this->imageResizeService->resize($file, $preset))
            : $this->disk()->putFileAs($directory, $file, basename($path)) !== false;

        if (! $stored) {
            throw new RuntimeException("Unable to store uploaded file at [{$path}].");
        }

        return $path;
    }

    /**
     * @param  array<int, mixed>  $files
     * @return array<int, string>
     */
    public function uploadMany(array $files, string $directory, ?string $preset = null): array
    {
        $paths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->upload($file, $directory, $preset);
            }
        }

        return $paths;
    }

    /**
     * No-op for null, empty, missing, external URLs and static asset paths (/images/...).
     */
    public function delete(?string $path): void
    {
        if ($path === null || ! $this->isManagedPath($path)) {
            return;
        }

        $this->disk()->delete($path);
    }

    /**
     * Public URL for a stored path. Static (/images/...) and external paths are returned as-is.
     */
    public function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (! $this->isManagedPath($path)) {
            return $path;
        }

        return Storage::disk($this->diskName())->url($path);
    }

    private function isManagedPath(string $path): bool
    {
        return $path !== ''
            && ! str_starts_with($path, '/')
            && ! Str::startsWith($path, ['http://', 'https://'])
            && ! str_contains($path, '..');
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function diskName(): string
    {
        return config('media.disk');
    }
}
