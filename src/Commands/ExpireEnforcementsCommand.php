<?php

namespace EloquentWorks\Exile\Commands;

use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Models\Restriction;
use EloquentWorks\Exile\Services\AuditLogger;
use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Console\Command;

/**
 * Command to process newly expired bans and restrictions.
 */
final class ExpireEnforcementsCommand extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'exile:expire {--chunk=500 : Records processed per chunk}';

    /** @var string The console command description. */
    protected $description = 'Process newly expired bans and restrictions.';

    /**
     * Create a new command instance.
     *
     * @param  ExileManager  $exile  The ExileManager service for handling bans and restrictions.
     * @param  AuditLogger  $audit  The AuditLogger service for logging events.
     */
    public function __construct(
        private readonly ExileManager $exile,
        private readonly AuditLogger $audit,
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
        // Determine the chunk size for processing records
        $chunk = max(1, (int) $this->option('chunk'));
        $bans = 0;
        $restrictions = 0;

        /** @var class-string<Ban> $banModel */
        $banModel = config('exile.models.ban', Ban::class);

        // Process expired bans that have not been notified yet
        $banModel::query()
            ->expired()
            ->whereNull('expired_notified_at')
            ->chunkById($chunk, function ($records) use (&$bans): void {
                foreach ($records as $ban) {
                    if ($ban instanceof Ban && $this->exile->markBanExpired($ban)) {
                        $bans++;
                    }
                }
            });

        /** @var class-string<Restriction> $restrictionModel */
        $restrictionModel = config('exile.models.restriction', Restriction::class);

        // Process expired restrictions that have not been notified yet
        $restrictionModel::query()
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('expired_notified_at')
            ->chunkById($chunk, function ($records) use (&$restrictions): void {
                foreach ($records as $restriction) {
                    // Check if the record is an instance of Restriction
                    if (! $restriction instanceof Restriction) {
                        continue;
                    }

                    // Mark the restriction as expired and log the event
                    if ($restriction->forceFill(['expired_notified_at' => now()])->save()) {
                        $this->audit->log('restriction.expired', $restriction);
                        $restrictions++;
                    }
                }
            });

        // Output the results of the processing
        $this->components->info("Processed {$bans} expired bans and {$restrictions} expired restrictions.");

        // Return a success status code
        return self::SUCCESS;
    }
}
