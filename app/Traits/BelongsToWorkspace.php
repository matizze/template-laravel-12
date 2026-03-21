<?php

namespace App\Traits;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $query): void {
            $currentWorkspace = Workspace::current();

            if ($currentWorkspace) {
                $query->where('workspace_id', $currentWorkspace->id);
            }
        });

        static::creating(function ($model): void {
            if (! $model->workspace_id && Workspace::current()) {
                $model->workspace_id = Workspace::current()->id;
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
