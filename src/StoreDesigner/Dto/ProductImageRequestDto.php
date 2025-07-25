<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

use App\StoreDesigner\Util\ImageQuality;
use App\StoreDesigner\Util\ImageResolution;

final readonly class ProductImageRequestDto implements ImageRequestInterface
{
    public function __construct(
        public string $filename,
        public string $prompt,
        public string $model,
        public ImageResolution $imageResolution,
        public ImageQuality $imageQuality,
        public int $n,
    ) {
    }
}
