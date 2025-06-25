<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

final readonly class StoreConfigurationDto
{
    public function __construct(
        public string $industry,
        public ?string $locale = null,
        public ?string $currency = null,
        public ?string $theme = null
    ) {
    }
}
