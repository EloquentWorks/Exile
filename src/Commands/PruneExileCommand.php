<?php

namespace EloquentWorks\Exile\Commands;

use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Models\BanAppeal;
use EloquentWorks\Exile\Models\DeviceFingerprint;
use EloquentWorks\Exile\Models\ModerationAction;
use EloquentWorks\Exile\Models\Restriction;
use EloquentWorks\Exile\Models\Strike;
use EloquentWorks\Exile\Models\Warning;
use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Console\Command;

final class PruneExileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exile:prune {--days= : Override retention days} {--force : Prune even when disabled in config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old Exile moderation records and their evidence.';

    /**
     * Create a new command instance.
     *
     * @param  ExileManager  $exile  The ExileManager service for handling moderation records.
     * @return void
     */
    public function __construct(
        private readonly ExileManager $exile
    ) {
        // Call the parent constructor to ensure proper initialization
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int The exit status code of the command.
     */
    public function handle(): int
    {
        // Check if pruning is enabled in the configuration or if the --force option is provided
        if (! config('exile.retention.prune_enabled', false) && ! (bool) $this->option('force')) {
            $this->components->warn('Pruning is disabled. Use --force or enable exile.retention.prune_enabled.');

            // Exit the command with a success status code since pruning is not performed
            return self::SUCCESS;
        }

        // Determine the number of days to retain records, defaulting to the configuration value or 365 days
        $days = max(1, (int) ($this->option('days') ?: config('exile.retention.days', 365)));
        $cutoff = now()->subDays($days);
        $count = 0;

        /** @var class-string<Ban> $banModel */
        $banModel = config('exile.models.ban', Ban::class);
        $banModel::query()
            ->where('updated_at', '<', $cutoff)
            ->where(function ($query): void {
                $query->whereNotNull('revoked_at')
                    ->orWhere(fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()));
            })
            // Process records in chunks to avoid memory issues
            ->chunkById(200, function ($records) use (&$count): void {
                foreach ($records as $ban) {
                    foreach ($ban->evidence as $evidence) {
                        $this->exile->deleteEvidence($evidence);
                    }

                    // Delete the ban record itself
                    $ban->delete();
                    $count++;
                }
            });

        /** @var class-string<Restriction> $restrictionModel */
        $restrictionModel = config('exile.models.restriction', Restriction::class);
        $count += $restrictionModel::query()
            ->where('updated_at', '<', $cutoff)
            ->where(function ($query): void {
                $query->whereNotNull('revoked_at')
                    ->orWhere(fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()));
            })
            ->delete();

        /** @var class-string<Strike> $strikeModel */
        $strikeModel = config('exile.models.strike', Strike::class);
        $count += $strikeModel::query()
            ->where('updated_at', '<', $cutoff)
            ->where(function ($query): void {
                $query->whereNotNull('revoked_at')
                    ->orWhere(fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()));
            })
            ->delete();

        /** @var class-string<Warning> $warningModel */
        $warningModel = config('exile.models.warning', Warning::class);
        $count += $warningModel::query()->where('updated_at', '<', $cutoff)->whereNotNull('acknowledged_at')->delete();

        /** @var class-string<BanAppeal> $appealModel */
        $appealModel = config('exile.models.appeal', BanAppeal::class);
        $count += $appealModel::query()->where('updated_at', '<', $cutoff)->where('status', '!=', 'pending')->delete();

        /** @var class-string<DeviceFingerprint> $deviceModel */
        $deviceModel = config('exile.models.device_fingerprint', DeviceFingerprint::class);
        $count += $deviceModel::query()->where('last_seen_at', '<', $cutoff)->delete();

        /** @var class-string<ModerationAction> $actionModel */
        $actionModel = config('exile.models.action', ModerationAction::class);
        $count += $actionModel::query()->where('created_at', '<', $cutoff)->delete();

        // Display an informational message indicating the number of old Exile records that were pruned
        $this->components->info("Pruned {$count} old Exile records.");

        // Exit the command with a success status code
        return self::SUCCESS;
    }
}
