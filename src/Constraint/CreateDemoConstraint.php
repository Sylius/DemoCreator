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

namespace App\Constraint;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateDemoConstraint
{
    #[Assert\NotBlank(message: 'Field "environment" cannot be blank')]
    #[Assert\Type('string')]
    public ?string $environment = null;

    #[Assert\NotBlank(message: 'Field "store" cannot be blank')]
    #[Assert\Type('string')]
    public ?string $store = null;

    public function __construct(array $payload)
    {
        $this->environment = $payload['environment'] ?? null;
        $this->store = $payload['store'] ?? null;
    }
}
