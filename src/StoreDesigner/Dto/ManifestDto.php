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

final readonly class ManifestDto implements \JsonSerializable
{
    public function __construct(
        public ?string $name = null,
        public array $plugins = [],
    ) {
    }

    public function toJson(): string
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'plugins' => $this->plugins,
        ];
    }
}
