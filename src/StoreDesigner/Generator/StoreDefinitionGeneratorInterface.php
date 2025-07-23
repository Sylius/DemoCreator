<?php

declare(strict_types=1);

namespace App\StoreDesigner\Generator;

use App\StoreDesigner\Dto\StoreDetailsDto;

interface StoreDefinitionGeneratorInterface
{
    public function generate(StoreDetailsDto $storeDetailsDto): array;
}
