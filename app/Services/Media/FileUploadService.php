<?php

namespace App\Services\Media;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

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
     * A preset may define `variants` (e.g. slider => ['sm' => 'slider_sm']): the same
     * upload then also writes `directory/uuid-sm.ext`. Only the main path is returned;
     * delete() and srcset() derive the variant paths from it.
     *
     * Old files are never removed here: call delete() after the DB transaction commits.
     *
     * @throws InvalidArgumentException when the preset does not exist
     * @throws RuntimeException when a file cannot be written
     */
    public function upload(UploadedFile $file, string $directory, ?string $preset = null): string
    {
        $directory = trim($directory, '/');

        if ($preset === null || ! config('media.resize_enabled')) {
            return $this->storeOriginal($file, $directory);
        }

        $base = $directory.'/'.Str::uuid()->toString();
        $written = [];

        try {
            foreach ($this->outputs($preset) as $suffix => $outputPreset) {
                $path = $base.$suffix.'.'.$this->imageResizeService->extensionFor($outputPreset);

                if (! $this->disk()->put($path, $this->imageResizeService->resize($file, $outputPreset))) {
                    throw new RuntimeException("Unable to store uploaded file at [{$path}].");
                }

                $written[] = $path;
            }
        } catch (Throwable $e) {
            foreach ($written as $path) {
                $this->disk()->delete($path);
            }

            throw $e;
        }

        return $written[0];
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
     * Removes the file and all its size variants. No-op for null, empty, missing,
     * external URLs and static asset paths (/images/...).
     */
    public function delete(?string $path): void
    {
        if ($path === null || ! $this->isManagedPath($path)) {
            return;
        }

        foreach ($this->variantPaths($path) as $variantPath) {
            $this->disk()->delete($variantPath);
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

    /**
     * URL of a size variant written by upload() (e.g. the "thumb" of a product image).
     * Static (/images/...) and external paths are returned as-is.
     */
    public function variantUrl(?string $path, string $variant): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (! $this->isManagedPath($path)) {
            return $path;
        }

        return $this->url($this->withSuffix($path, "-{$variant}"));
    }

    /**
     * `srcset` value ("<url> 960w, <url> 1920w") for a file uploaded with a preset that has
     * variants. Null when the path is static/external or fewer than two sizes exist.
     */
    public function srcset(?string $path, string $preset): ?string
    {
        if ($path === null || ! $this->isManagedPath($path)) {
            return null;
        }

        $candidates = [];

        foreach ($this->outputs($preset) as $suffix => $outputPreset) {
            $candidatePath = $this->withSuffix($path, $suffix);

            if ($this->disk()->exists($candidatePath)) {
                $candidates[$this->imageResizeService->widthFor($outputPreset)] = $this->url($candidatePath);
            }
        }

        if (count($candidates) < 2) {
            return null;
        }

        ksort($candidates);

        return collect($candidates)->map(fn (string $url, int $width) => "{$url} {$width}w")->implode(', ');
    }

    private function storeOriginal(UploadedFile $file, string $directory): string
    {
        $filename = Str::uuid()->toString().'.'.$file->extension();

        if ($this->disk()->putFileAs($directory, $file, $filename) === false) {
            throw new RuntimeException("Unable to store uploaded file at [{$directory}/{$filename}].");
        }

        return $directory.'/'.$filename;
    }

    /**
     * Preset to generate per filename suffix: the preset itself ("") plus its variants.
     *
     * @return array<string, string>
     */
    private function outputs(string $preset): array
    {
        $outputs = ['' => $preset];

        foreach ((array) config("media.presets.{$preset}.variants", []) as $key => $variantPreset) {
            $outputs["-{$key}"] = $variantPreset;
        }

        return $outputs;
    }

    /**
     * Every variant path a main path could have, taken from all presets' variant keys.
     *
     * @return array<int, string>
     */
    private function variantPaths(string $path): array
    {
        return collect((array) config('media.presets'))
            ->flatMap(fn (array $preset) => array_keys($preset['variants'] ?? []))
            ->unique()
            ->map(fn (string $key) => $this->withSuffix($path, "-{$key}"))
            ->values()
            ->all();
    }

    private function withSuffix(string $path, string $suffix): string
    {
        if ($suffix === '') {
            return $path;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return substr($path, 0, -(strlen($extension) + 1)).$suffix.'.'.$extension;
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
