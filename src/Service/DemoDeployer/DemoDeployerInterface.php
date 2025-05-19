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

namespace App\Service\DemoDeployer;

interface DemoDeployerInterface
{
    /**
     * @param array<string,string> $plugins
     *
     * @return array{status:string, url:string}
     */
    public function deploy(string $slug, array $plugins): array;

    public function getProviderKey(): string;
}
