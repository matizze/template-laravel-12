<?php

namespace Modules\Workspace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\User\Models\User;
use Modules\Workspace\Database\Factories\WorkspaceFactory;
use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Services\CurrentWorkspaceManager;

class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'logo_path', 'user_id'];

    protected static function newFactory(): WorkspaceFactory
    {
        return WorkspaceFactory::new();
    }

    public static function current(): ?self
    {
        return app(CurrentWorkspaceManager::class)->get();
    }

    public static function setCurrent(?int $workspaceId): void
    {
        $manager = app(CurrentWorkspaceManager::class);

        if ($workspaceId) {
            $manager->setById($workspaceId);
        } else {
            $manager->forget();
        }
    }

    public static function setCurrentModel(self $workspace): void
    {
        app(CurrentWorkspaceManager::class)->set($workspace);
    }

    public static function forgetCurrent(): void
    {
        app(CurrentWorkspaceManager::class)->forget();
    }

    protected static function booted(): void
    {
        static::creating(function (Workspace $workspace): void {
            if (! $workspace->slug) {
                $workspace->slug = Str::slug($workspace->name);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'members')
            ->using(Member::class)
            ->withPivot('id', 'role')
            ->withCasts(['role' => WorkspaceRole::class])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}
