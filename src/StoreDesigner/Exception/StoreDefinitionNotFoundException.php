<?php

namespace App\StoreDesigner\Exception;

class StoreDefinitionNotFoundException extends \RuntimeException
{
    public function __construct(string $presetId)
    {
        parent::__construct(sprintf('Store definition for preset with ID "%s" not found.', $presetId));
    }
}
