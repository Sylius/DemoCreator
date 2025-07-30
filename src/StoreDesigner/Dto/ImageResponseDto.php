<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

use App\StoreDesigner\Util\ImageType;

final readonly class ImageResponseDto
{
    public function __construct(
        public string $filename,
        public ImageType $imageType,
        public string $binary,
    ) {
    }
}
