<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

use App\StoreDesigner\Util\ImageBackground;
use App\StoreDesigner\Util\ImageQuality;
use App\StoreDesigner\Util\ImageResolution;

final readonly class CustomImageRequestDto implements ImageRequestInterface
{
    public function __construct(
        public string $filename,
        public string $prompt,
        public string $model,
        public ImageResolution $imageResolution,
        public ImageQuality $imageQuality,
        public ImageBackground $imageBackground,
        public int $n,
    ) {
    }
}
