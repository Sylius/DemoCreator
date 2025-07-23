<?php
declare(strict_types=1);

namespace App\StoreDesigner\Dto;

final readonly class ImageRequestDto
{
    public function __construct(
        public string $prompt,
        public string $model = 'gpt-image-1',
        public string $size = '1024x1024',
        public string $quality = 'low',
        public int $n = 1,
    ) {
    }
}
