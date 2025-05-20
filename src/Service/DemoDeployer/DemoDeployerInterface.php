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

use App\Exception\DemoDeploymentException;

interface DemoDeployerInterface
{
    /**
     * @param string $environment slug for the new demo environment
     * @param string[] $plugins [package => version]
     * @return array{status:string, url:string}
     * @throws DemoDeploymentException
     */
    public function deploy(string $environment, array $plugins): array;

    public function getProviderKey(): string;
}
