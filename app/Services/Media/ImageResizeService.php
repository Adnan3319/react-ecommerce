<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncoderInterface;
use InvalidArgumentException;

final class ImageResizeService
{
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

        $image = $this->manager->decodePath($file->getRealPath());

        if ($config['fit'] === 'cover') {
            $image->cover($config['width'], $config['height']);
        } else {
            $image->scaleDown($config['width'], $config['height']);
        }

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
