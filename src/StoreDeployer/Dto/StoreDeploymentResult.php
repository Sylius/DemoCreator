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

namespace App\StoreDeployer\Dto;

use App\StoreDeployer\ValueObject\StoreDeploymentStatus;

final readonly class StoreDeploymentResult implements \JsonSerializable
{
    public function __construct(
        public StoreDeploymentStatus $status,
        public array $customOptions = []
    ) {
    }

    public function toJson(): string
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status->value,
            'customOptions' => $this->customOptions,
        ];
    }
}
