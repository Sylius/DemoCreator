<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

final class StoreDetailsDto
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
