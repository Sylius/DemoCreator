<?php

namespace App\StoreDesigner\Schema;

readonly class JsonSchema
{
    public function __construct(
        public array $raw
    ) {
    }

    public function toObject(): \stdClass
    {
        return json_decode(json_encode($this->raw, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
    }
}
