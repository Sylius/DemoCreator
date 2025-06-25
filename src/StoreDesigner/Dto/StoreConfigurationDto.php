<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

final readonly class StoreConfigurationDto
{
    public function __construct(
        public string $industry,
        public array $locales = [],
        public array $currencies = [],
        public array $countries = [],
        public array $categories = [],
        public int $productsPerCat = 0,
        public ?string $descriptionStyle = null,
        public ?string $imageStyle = null,
        public array $zones = [],
    ) {
    }
}
