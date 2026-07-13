<?php

namespace EloquentWorks\Exile\Services;

use DateInterval;
use EloquentWorks\Exile\Enums\BanType;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Models\ModerationAction;
use EloquentWorks\Exile\Models\Strike;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Service responsible for evaluating and applying moderation escalations based on strike points.
 */
final class EscalationEngine
{
    /**
     * Constructs a new instance of the EscalationEngine.
     *
     * @param  EnforcementWriter  $writer  The enforcement writer for issuing bans and restrictions.
     * @param  AuditLogger  $audit  The audit logger for logging escalation actions.
     */
    public function __construct(
        private readonly EnforcementWriter $writer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Evaluates the given account for potential moderation escalations based on strike points.
     *
     * @param  Model  $account  The account to evaluate for escalation.
     */
    public function evaluate(Model $account): void
    {
        // Check if escalation is enabled in the configuration
        if (! config('exile.escalation.enabled', true)) {
            return;
        }

        /** @var class-string<Strike> $strikeModel */
        $strikeModel = config('exile.models.strike', Strike::class);

        // Calculate the total active strike points for the account
        $points = (int) $strikeModel::query()
            ->where('strikeable_type', $account->getMorphClass())
            ->where('strikeable_id', $account->getKey())
            ->active()
            ->sum('points');

        /** @var list<array<string, mixed>> $thresholds */
        $thresholds = config('exile.escalation.thresholds', []);
        usort($thresholds, static fn (array $a, array $b): int => ((int) ($b['points'] ?? 0)) <=> ((int) ($a['points'] ?? 0)));

        // Iterate through the configured thresholds to determine if any escalation actions should be applied
        foreach ($thresholds as $threshold) {
            // Determine the required points for this threshold, defaulting to PHP_INT_MAX if not specified
            $required = (int) ($threshold['points'] ?? PHP_INT_MAX);

            // Check if the account's points meet the threshold and if the escalation has already been applied
            if ($points < $required || $this->wasApplied($account, $required)) {
                continue;
            }

            // Determine the expiration time for the escalation action based on the configured duration
            $expiresAt = $this->expiration((string) ($threshold['duration'] ?? ''));
            $reason = (string) ($threshold['reason'] ?? 'Automatic moderation escalation.');
            $action = (string) ($threshold['action'] ?? '');
            $metadata = ['source' => 'automatic_escalation', 'threshold' => $required, 'active_points' => $points];

            // Apply the appropriate escalation action based on the configured action type (ban or restriction)
            if ($action === 'ban') {
                $type = BanType::tryFrom((string) ($threshold['type'] ?? 'account')) ?? BanType::Account;
                $this->writer->issueBan(
                    type: $type,
                    account: $account,
                    reason: $reason,
                    expiresAt: $expiresAt,
                    metadata: $metadata,
                );
            } elseif ($action === 'restriction') {
                $type = RestrictionType::tryFrom((string) ($threshold['type'] ?? 'posting')) ?? RestrictionType::Posting;
                $this->writer->issueRestriction(
                    account: $account,
                    type: $type,
                    reason: $reason,
                    expiresAt: $expiresAt,
                    metadata: $metadata,
                );
            } else {
                continue;
            }

            // Log the escalation action in the audit log
            $this->audit->log('escalation.applied', $account, null, $metadata + ['action' => $action]);

            // Exit the loop after applying the first applicable escalation action
            return;
        }
    }

    /**
     * Checks if an escalation action has already been applied to the given account for the specified threshold.
     *
     * @param  Model  $account  The account to check for applied escalations.
     * @param  int  $threshold  The threshold points to check against.
     * @return bool Returns true if the escalation action has already been applied, false otherwise.
     */
    private function wasApplied(Model $account, int $threshold): bool
    {
        /** @var class-string<ModerationAction> $actionModel */
        $actionModel = config('exile.models.action', ModerationAction::class);

        // Query the moderation actions to check if an escalation action has already been applied for the given account and threshold
        return $actionModel::query()
            ->where('action', 'escalation.applied')
            ->where('subject_type', $account->getMorphClass())
            ->where('subject_id', $account->getKey())
            ->get()
            ->contains(static fn (ModerationAction $action): bool => (int) data_get($action->context, 'threshold') === $threshold);
    }

    /**
     * Calculates the expiration time based on the given duration string.
     *
     * @param  string  $duration  The duration string (e.g., 'P1D' for 1 day).
     * @return \DateTimeInterface|null Returns the calculated expiration time or null if the duration is empty or invalid.
     */
    private function expiration(string $duration): ?\DateTimeInterface
    {
        // If the duration string is empty, return null to indicate no expiration time.
        if ($duration === '') {
            return null;
        }

        // Attempt to create a DateInterval from the duration string and add it to the current time. If an exception occurs, return null.
        try {
            return now()->add(new DateInterval($duration));
        } catch (Throwable) {
            return null;
        }
    }
}
