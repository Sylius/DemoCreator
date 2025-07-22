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

namespace App\StoreDesigner\Service;

use Symfony\Component\Uid\Uuid;

final class StorePresetIdGenerator
{
    public function generate(): string
    {
        $timestamp = (new \DateTimeImmutable())->format('Ymd_His');
        $uuid = Uuid::v4()->toRfc4122();

        return "{$timestamp}_$uuid";
    }
}
