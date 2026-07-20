<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentSource: string
{
    case Web = 'web';
    case AiChat = 'ai_chat';
}
