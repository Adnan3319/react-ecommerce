<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Fills created_by / updated_by / deleted_by from the authenticated user.
 * Only use on models whose table has those columns.
 */
trait HasAuditFields
{
    protected static function bootHasAuditFields(): void
    {
        static::creating(function (Model $model): void {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function (Model $model): void {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        // Soft delete only touches deleted_at, so deleted_by needs its own write.
        static::deleting(function (Model $model): void {
            if (Auth::check() && method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                $model->forceFill(['deleted_by' => Auth::id()])->saveQuietly();
            }
        });
    }
}
