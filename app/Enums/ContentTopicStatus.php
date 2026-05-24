<?php

namespace App\Enums;

enum ContentTopicStatus: string
{
    case Proposed = 'proposed';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Published = 'published';
    case Archived = 'archived';
}
