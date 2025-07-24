<?php
declare(strict_types=1);

namespace App\StoreDesigner\Dto;

use App\StoreDesigner\Util\ImageQuality;
use App\StoreDesigner\Util\ImageResolution;

final readonly class ImageRequestDto
{
    public function __construct(
        public string $prompt,
        public string $model = 'gpt-image-1',
        public ImageResolution $imageResolution = ImageResolution::Square,
        public ImageQuality $imageQuality = ImageQuality::Low,
        public int $n = 1,
    ) {
    }
}
