<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

enum ChatConversationState: string
{
    case Collecting = 'collecting';
    case Ready = 'ready';
    case Generating = 'generating';
    case Done = 'done';
    case Error = 'error';
}
