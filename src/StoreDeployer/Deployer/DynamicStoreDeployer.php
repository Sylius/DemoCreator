<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\StoreDeployer\Deployer;

use Symfony\Component\DependencyInjection\ServiceLocator;

final readonly class DynamicStoreDeployer implements StoreDeployerInterface
{
    public function __construct(
        private ServiceLocator $locator,
        private string $target,
    ) {
    }

    public function deploy(string $storePresetId): StoreDeploymentStatus
    {
        if (!$this->locator->has($this->target)) {
            throw new \InvalidArgumentException(
                sprintf('Unknown deploy target "%s".', $this->target)
            );
        }

        /** @var StoreDeployerInterface $deployer */
        $deployer = $this->locator->get($this->target);

        return $deployer->deploy($storePresetId);
    }
}
