<?php

namespace App\Enums;

enum AiRunType: string
{
    case Topic = 'topic';
    case Outline = 'outline';
    case Draft = 'draft';
    case Review = 'review';
    case Revision = 'revision';
    case Translation = 'translation';
    case Image = 'image';
    case Freshness = 'freshness';
}
