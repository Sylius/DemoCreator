<?php

namespace App\Message;

final readonly class CreateDemoMessage
{
    /* @param array<string, string> $configuration */
     public function __construct(
         public array $configuration,
     ) {
     }
}
