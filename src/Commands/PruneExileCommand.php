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
    protected $signature = 'exile:prune {--days= : Override retention days} {--force : Prune even when disabled in config}';

    protected $description = 'Prune old Exile moderation records and their evidence.';

    public function __construct(private readonly ExileManager $exile)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! config('exile.retention.prune_enabled', false) && ! (bool) $this->option('force')) {
            $this->components->warn('Pruning is disabled. Use --force or enable exile.retention.prune_enabled.');

            return self::SUCCESS;
        }

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
            ->chunkById(200, function ($records) use (&$count): void {
                foreach ($records as $ban) {
                    if (! $ban instanceof Ban) {
                        continue;
                    }

                    foreach ($ban->evidence as $evidence) {
                        $this->exile->deleteEvidence($evidence);
                    }

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

        $this->components->info("Pruned {$count} old Exile records.");

        return self::SUCCESS;
    }
}
