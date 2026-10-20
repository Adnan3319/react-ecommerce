<?php

namespace App\Enums;

/**
 * Where a slider is shown: the Home hero, or the promo banner. Each position has its own image
 * preset (config/media.php), minimum upload size and limit of active sliders.
 */
enum SliderPosition: string
{
    case Hero = 'hero';
    case Promo = 'promo';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Image preset (with its size variants) the uploads of this position are resized with.
     */
    public function preset(): string
    {
        return match ($this) {
            self::Hero => 'slider',
            self::Promo => 'promo',
        };
    }

    public function minWidth(): int
    {
        return match ($this) {
            self::Hero => 1920,
            self::Promo => 1200,
        };
    }

    public function minHeight(): int
    {
        return 800;
    }

    /**
     * Upload rule shown under the image field of the admin form (the limits are enforced by SaveSliderRequest).
     */
    public function imageHint(): string
    {
        return sprintf('Wide image, at least %dx%d, max 2MB', $this->minWidth(), $this->minHeight());
    }
}
