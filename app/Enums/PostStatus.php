<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case NeedsAttention = 'needs_attention';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';
}
