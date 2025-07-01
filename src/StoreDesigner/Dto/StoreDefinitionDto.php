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

final class StoreDefinitionDto implements \JsonSerializable
{
    public function __construct(
        public string $industry,
        public array $locales,
        public array $currencies,
        public array $countries,
        public array $categories,
        public int $productsPerCat,
        public string $descriptionStyle,
        public string $imageStyle,
        public array $zones,
    ) {
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    public function jsonSerialize(): mixed
    {
        return [
            'industry' => $this->industry,
            'locales' => $this->locales,
            'currencies' => $this->currencies,
            'countries' => $this->countries,
            'categories' => $this->categories,
            'productsPerCat' => $this->productsPerCat,
            'descriptionStyle' => $this->descriptionStyle,
            'imageStyle' => $this->imageStyle,
            'zones' => $this->zones,
        ];
    }
}
