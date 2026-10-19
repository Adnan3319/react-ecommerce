<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncoderInterface;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;

final class ImageResizeService
{
    /** Padding colour for `contain` (keeps transparency in webp/png). */
    private const TRANSPARENT = 'rgba(0,0,0,0)';

    private readonly ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Resizes and re-encodes an uploaded image per a named preset from
     * config('media.presets'), keeping stored file size predictable.
     *
     * @throws InvalidArgumentException when the preset does not exist
     */
    public function resize(UploadedFile $file, string $preset): string
    {
        $config = $this->presetConfig($preset);

        $image = $this->fit($this->manager->decodePath($file->getRealPath()), $config);

        return (string) $image->encode($this->encoderFor($config['format'], $config['quality']));
    }

    /**
     * @throws InvalidArgumentException when the preset does not exist
     */
    public function extensionFor(string $preset): string
    {
        return $this->presetConfig($preset)['format'];
    }

    /**
     * Width of the file a preset produces (used to build srcset descriptors).
     *
     * @throws InvalidArgumentException when the preset does not exist
     */
    public function widthFor(string $preset): int
    {
        return $this->presetConfig($preset)['width'];
    }

    /**
     * @param  array{width: int, height: int, fit: string}  $config
     */
    private function fit(ImageInterface $image, array $config): ImageInterface
    {
        return match ($config['fit']) {
            'cover' => $image->cover($config['width'], $config['height']),
            'contain' => $image->contain($config['width'], $config['height'], self::TRANSPARENT),
            'scale_down' => $image->scaleDown($config['width'], $config['height']),
            default => throw new InvalidArgumentException("Unknown image fit [{$config['fit']}]."),
        };
    }

    /**
     * @return array{width: int, height: int, fit: string, format: string, quality: int}
     */
    private function presetConfig(string $preset): array
    {
        $config = config("media.presets.{$preset}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Unknown image preset [{$preset}].");
        }

        return $config;
    }

    private function encoderFor(string $format, int $quality): EncoderInterface
    {
        return match ($format) {
            'jpg', 'jpeg' => new JpegEncoder(quality: $quality),
            'png' => new PngEncoder,
            default => new WebpEncoder(quality: $quality),
        };
    }
}
