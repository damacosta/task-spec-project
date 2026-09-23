<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use AlizHarb\ActivityLog\Taps\SetActivityContextTap;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Bundles the Spatie activity-logging setup for a model:
 *
 * - composes HasActivity (log its own events + act as a causer);
 * - logs every attribute except the ones listed in $hidden (secrets stay out
 *   of the audit trail — redaction is display-only and cannot protect the DB,
 *   exports or backups);
 * - feeds request context (ip_address, request_id, user_agent, tenant) into the
 *   activity properties on every logged event. Spatie v5 no longer reads
 *   config('activitylog.activity_logger_taps'), so the package tap only runs
 *   when invoked from the model's beforeActivityLogged hook. AuditMetadata later
 *   copies ip_address and request_id into the audit columns.
 */
trait CapturesActivityContext
{
    use HasActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(static::class)
            ->logAll()
            ->logExcept($this->hidden);
    }

    public function beforeActivityLogged(Activity $activity, string $event): void
    {
        if (!config('filament-activity-log.auto_context.enabled', default: true)) {
            return;
        }

        $subject = $this instanceof Model ? $this : null;

        $tap = resolve(SetActivityContextTap::class);
        $tap($activity, $event, $subject, $activity->causer);
    }
}
