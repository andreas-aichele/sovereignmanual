<?php

namespace App\Enums;

enum ContentType: string
{
    case Guide = 'guide';
    case Checklist = 'checklist';
    case Analysis = 'analysis';
    case Briefing = 'briefing';
}
