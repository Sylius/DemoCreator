<?php

namespace App\Message;

final readonly class RunPluginManagerMessage
{
    /* @param array<string, string> $plugins */
     public function __construct(
         public array $plugins,
     ) {
     }
}
