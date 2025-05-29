<?php

namespace App\Message;

final readonly class RunThemeCreatorMessage
{
    /* @param array<string, string> $themes */
     public function __construct(
         public array $themes,
     ) {
     }
}
