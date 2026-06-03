<?php

namespace App\Enums;

enum AiRunType: string
{
    case Topic = 'topic';
    case Outline = 'outline';
    case Draft = 'draft';
    case Revision = 'revision';
    case Translation = 'translation';
    case Image = 'image';
}
