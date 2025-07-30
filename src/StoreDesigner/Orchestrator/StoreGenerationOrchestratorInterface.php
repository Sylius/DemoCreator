<?php

declare(strict_types=1);

namespace App\StoreDesigner\Orchestrator;

use App\StoreDesigner\Dto\StoreDetailsDto;

interface StoreGenerationOrchestratorInterface
{
    public function orchestrate(string $storePresetId, StoreDetailsDto $storeDetailsDto): void;
}
