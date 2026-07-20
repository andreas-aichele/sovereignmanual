<?php

namespace App\Enums;

enum NewsletterDeliveryStatus: string
{
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
