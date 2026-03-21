<?php

namespace App\Traits;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::creating(function ($model) {
            if (empty($model->workspace_id) && Workspace::current()) {
                $model->workspace_id = Workspace::current()->id;
            }
        });

        static::addGlobalScope('workspace', function (Builder $builder) {
            $workspace = Workspace::current();

            if ($workspace) {
                $builder->where($builder->qualifyColumn('workspace_id'), $workspace->id);
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function scopeWithoutWorkspaceScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('workspace');
    }
}
