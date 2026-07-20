<?php

namespace App\Console\Commands;

use App\Actions\DispatchWeeklyNewsletter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-weekly-newsletter')]
#[Description('Queue the automatic weekly newsletter summary for confirmed subscribers.')]
class SendWeeklyNewsletter extends Command
{
    public function handle(DispatchWeeklyNewsletter $dispatch): int
    {
        $issues = $dispatch->handle(now());

        if ($issues === []) {
            $this->info('No published posts were available for the weekly newsletter.');

            return self::SUCCESS;
        }

        $deliveries = collect($issues)->sum(fn ($issue): int => $issue->deliveries()->count());

        $this->info("Queued {$deliveries} weekly newsletter deliveries across ".count($issues).' issue(s).');

        return self::SUCCESS;
    }
}
