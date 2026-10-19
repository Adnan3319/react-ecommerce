<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait HasAuditFields
{
    protected static function bootHasAuditFields()
    {
        static::creating(function ($model) {
            if (Schema::hasColumn($model->getTable(), 'created_by') && Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Schema::hasColumn($model->getTable(), 'updated_by') && Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }
}