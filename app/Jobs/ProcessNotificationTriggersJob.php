<?php

namespace App\Jobs;

use App\Services\NotificationTriggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessNotificationTriggersJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationTriggerService $notificationService): void
    {
        $triggers = $notificationService->pending();

        foreach ($triggers as $trigger) {
            try {
                // Phase 1: log the trigger. Wire a real notification provider here
                // (push, email, webhook) by listening on the trigger_type.
                Log::info('notification_trigger_fired', [
                    'trigger_id'   => $trigger->id,
                    'user_id'      => $trigger->user_id,
                    'creator_app_id' => $trigger->creator_app_id,
                    'trigger_type' => $trigger->trigger_type,
                    'payload'      => $trigger->payload,
                ]);

                $notificationService->markSent($trigger);
            } catch (\Throwable $e) {
                Log::error('notification_trigger_failed', [
                    'trigger_id' => $trigger->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
