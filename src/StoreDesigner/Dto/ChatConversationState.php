<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

enum ChatConversationState: string
{
    case Collecting = 'collecting';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Generating = 'generating';
    case Done = 'done';
    case Error = 'error';
}
