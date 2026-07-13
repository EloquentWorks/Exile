<?php

namespace EloquentWorks\Exile\Services;

use EloquentWorks\Exile\Models\ModerationAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Service responsible for logging moderation actions.
 */
final class AuditLogger
{
    /**
     * Log a moderation action.
     *
     * @param  string  $action  The action being logged.
     * @param  Model|null  $subject  The subject of the action.
     * @param  Model|null  $actor  The actor performing the action.
     * @param  array<string, mixed>  $context  Additional context for the action.
     * @return ModerationAction|null The created moderation action record, or null if logging is disabled.
     */
    public function log(string $action, ?Model $subject = null, ?Model $actor = null, array $context = []): ?ModerationAction
    {
        // Check if audit logging is enabled in the configuration
        if (! config('exile.audit.enabled', true)) {
            return null;
        }

        /** @var class-string<ModerationAction> $modelClass */
        $modelClass = config('exile.models.action', ModerationAction::class);

        /** @var ModerationAction $record */
        $record = $modelClass::query()->create([
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'context' => $context,
        ]);

        // Log the action to the default log channel for additional visibility
        return $record;
    }
}
