<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use App\Services\CurrentWorkspaceManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Workspace extends Model
{
    /** @use HasFactory<\Database\Factories\WorkspaceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'logo_path', 'user_id'];

    /**
     * Retorna o workspace atual da requisicao via CurrentWorkspaceManager.
     */
    public static function current(): ?self
    {
        return app(CurrentWorkspaceManager::class)->get();
    }

    /**
     * Define o workspace atual pelo ID.
     */
    public static function setCurrent(?int $workspaceId): void
    {
        if ($workspaceId) {
            app(CurrentWorkspaceManager::class)->setById($workspaceId);
        } else {
            app(CurrentWorkspaceManager::class)->forget();
        }
    }

    /**
     * Define o workspace atual a partir de uma instancia do modelo.
     */
    public static function setCurrentModel(self $workspace): void
    {
        app(CurrentWorkspaceManager::class)->set($workspace);
    }

    /**
     * Limpa o workspace atual da memoria e da sessao.
     */
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
