<?php

namespace App\Enums;

enum WaitlistSubscriberStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Unsubscribed = 'unsubscribed';
}
