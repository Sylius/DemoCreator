<?php

namespace App\StoreDesigner\Exception;

class InvalidStoreDefinitionException extends \RuntimeException
{
    public function __construct($message = "Invalid store definition")
    {
        parent::__construct($message);
    }
}
