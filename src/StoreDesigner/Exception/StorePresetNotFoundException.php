<?php

namespace App\StoreDesigner\Exception;

class StorePresetNotFoundException extends \RuntimeException
{
    public function __construct(string $presetId)
    {
        parent::__construct(sprintf('Store preset with ID "%s" not found.', $presetId));
    }
}
