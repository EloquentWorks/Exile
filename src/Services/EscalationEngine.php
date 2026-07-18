<?php

namespace EloquentWorks\Exile\Services;

use DateInterval;
use DateTimeInterface;
use EloquentWorks\Exile\Enums\BanType;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Models\AppliedEscalation;
use EloquentWorks\Exile\Models\Strike;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The EscalationEngine class is responsible for evaluating and applying automatic moderation escalations
 * based on the accumulated strike points of an account. It checks configured thresholds and applies
 * bans or restrictions accordingly.
 */
final class EscalationEngine
{
    /**
     * Create a new instance of the EscalationEngine.
     *
     * @param  EnforcementWriter  $writer  The enforcement writer used to issue bans and restrictions.
     * @param  AuditLogger  $audit  The audit logger used to log escalation actions.
     */
    public function __construct(
        private readonly EnforcementWriter $writer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Evaluate the account for automatic moderation escalations.
     *
     * This method checks if automatic escalation is enabled in the configuration. If enabled,
     * it locks the account record for update and evaluates the accumulated strike points against
     * configured thresholds. If a threshold is met, it issues the corresponding ban or restriction.
     *
     * @param  Model  $account  The account model to evaluate for escalation.
     */
    public function evaluate(Model $account): void
    {
        // Check if automatic escalation is enabled in the configuration
        if (
            ! config(
                'exile.escalation.enabled',
                true
            )
        ) {
            return;
        }

        // Perform the evaluation within a database transaction to ensure atomicity
        DB::transaction(
            function () use ($account): void {
                // Lock the account record for update to prevent race conditions
                $account->newQuery()
                    ->whereKey($account->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                // Evaluate the account for escalation based on accumulated strike points
                $this->evaluateLocked($account);
            }
        );
    }

    /**
     * Evaluate the account for escalation while holding a database lock.
     *
     * This method retrieves the accumulated strike points for the account and checks against
     * configured thresholds. If a threshold is met, it issues the corresponding ban or restriction.
     *
     * @param  Model  $account  The account model to evaluate for escalation.
     */
    private function evaluateLocked(
        Model $account
    ): void {
        /** @var class-string<Strike> $strikeModel */
        $strikeModel = config(
            'exile.models.strike',
            Strike::class
        );

        // Retrieve the total active strike points for the account
        $points = (int) $strikeModel::query()
            ->where(
                'strikeable_type',
                $account->getMorphClass()
            )
            ->where(
                'strikeable_id',
                $account->getKey()
            )
            ->active()
            ->sum('points');

        /** @var list<array<string, mixed>> $thresholds */
        $thresholds = config(
            'exile.escalation.thresholds',
            []
        );

        // Sort the thresholds in descending order based on the required points
        usort(
            $thresholds,
            static fn (
                array $left,
                array $right
            ): int => (
                (int) ($right['points'] ?? 0)
            ) <=> (
                (int) ($left['points'] ?? 0)
            )
        );

        // Iterate through the thresholds and apply the first one that meets the criteria
        foreach ($thresholds as $threshold) {
            // Check if the threshold has already been applied for this account
            $requiredPoints = (int) (
                $threshold['points']
                ?? PHP_INT_MAX
            );

            // If the account's active points are less than the required points for this threshold, skip to the next threshold
            if ($points < $requiredPoints) {
                continue;
            }

            // Determine the action to take (ban or restriction) based on the threshold configuration
            $action = (string) (
                $threshold['action']
                ?? ''
            );

            // If the action is not recognized (not 'ban' or 'restriction'), skip to the next threshold
            if (
                ! in_array(
                    $action,
                    ['ban', 'restriction'],
                    true
                )
            ) {
                continue;
            }

            // Attempt to reserve the threshold for this account to prevent duplicate escalations
            if (
                ! $this->reserveThreshold(
                    $account,
                    $requiredPoints,
                    $action,
                    $points
                )
            ) {
                continue;
            }

            // Determine the expiration time for the ban or restriction based on the threshold configuration
            $expiresAt = $this->expiration(
                (string) (
                    $threshold['duration']
                    ?? ''
                )
            );

            // Determine the reason for the ban or restriction based on the threshold configuration
            $reason = (string) (
                $threshold['reason']
                ?? 'Automatic moderation escalation.'
            );

            // Prepare metadata for logging and auditing purposes
            $metadata = [
                'source' => 'automatic_escalation',
                'threshold' => $requiredPoints,
                'active_points' => $points,
            ];

            // Issue the ban or restriction based on the determined action
            if ($action === 'ban') {
                // Determine the type of ban based on the threshold configuration, defaulting to 'Account' if not specified
                $type = BanType::tryFrom(
                    (string) (
                        $threshold['type']
                        ?? BanType::Account->value
                    )
                ) ?? BanType::Account;

                // Issue the ban using the enforcement writer
                $this->writer->issueBan(
                    type: $type,
                    account: $account,
                    reason: $reason,
                    expiresAt: $expiresAt,
                    metadata: $metadata,
                );
            } else {
                // Determine the type of restriction based on the threshold configuration, defaulting to 'Posting' if not specified
                $type = RestrictionType::tryFrom(
                    (string) (
                        $threshold['type']
                        ?? RestrictionType::Posting->value
                    )
                ) ?? RestrictionType::Posting;

                // Issue the restriction using the enforcement writer
                $this->writer->issueRestriction(
                    account: $account,
                    type: $type,
                    reason: $reason,
                    expiresAt: $expiresAt,
                    metadata: $metadata,
                );
            }

            // Log the escalation action in the audit log for tracking and accountability
            $this->audit->log(
                'escalation.applied',
                $account,
                null,
                $metadata + [
                    'action' => $action,
                ]
            );

            // After applying the first applicable threshold, exit the loop to prevent multiple escalations in a single evaluation
            return;
        }
    }

    /**
     * Reserve a threshold for the account to prevent duplicate escalations.
     *
     * This method attempts to insert a record into the applied escalations table to indicate that
     * the specified threshold has been applied for the account. If the insertion is successful,
     * it returns true; otherwise, it returns false, indicating that the threshold has already been applied.
     *
     * @param  Model  $account  The account model for which the threshold is being reserved.
     * @param  int  $thresholdPoints  The number of points required for the threshold.
     * @param  string  $action  The action associated with the threshold (e.g., 'ban' or 'restriction').
     * @param  int  $activePoints  The current active points of the account.
     * @return bool Returns true if the threshold was successfully reserved; false if it was already applied.
     */
    private function reserveThreshold(
        Model $account,
        int $thresholdPoints,
        string $action,
        int $activePoints
    ): bool {
        /** @var class-string<AppliedEscalation> $modelClass */
        $modelClass = config(
            'exile.models.escalation',
            AppliedEscalation::class
        );

        // Get the current timestamp to use for the created_at and updated_at fields
        $timestamp = now();

        // Attempt to insert a new record into the applied escalations table with the specified threshold details.
        return $modelClass::query()
            ->insertOrIgnore([
                'escalatable_type' => $account
                    ->getMorphClass(),
                'escalatable_id' => $account
                    ->getKey(),
                'threshold_points' => $thresholdPoints,
                'action' => $action,
                'metadata' => json_encode(
                    [
                        'active_points' => $activePoints,
                    ],
                    JSON_THROW_ON_ERROR
                ),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]) === 1;
    }

    /**
     * Calculate the expiration time based on the provided duration string.
     *
     * This method attempts to create a DateInterval from the provided duration string and adds it
     * to the current time. If the duration string is empty or invalid, it returns null.
     *
     * @param  string  $duration  The duration string (e.g., 'P1D' for 1 day).
     * @return DateTimeInterface|null Returns the calculated expiration time or null if invalid.
     */
    private function expiration(
        string $duration
    ): ?DateTimeInterface {
        // If the duration string is empty, return null to indicate no expiration.
        if ($duration === '') {
            return null;
        }

        // Attempt to create a DateInterval from the provided duration string and add it to the current time.
        try {
            return now()->add(
                new DateInterval($duration)
            );
        } catch (Throwable) {
            // If the duration string is invalid, return null to indicate no expiration.
            return null;
        }
    }
}
