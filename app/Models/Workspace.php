<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'slug', 'description'];

    public static function current(): ?self
    {
        return app('current.workspace');
    }

    public static function setCurrent(?self $workspace): void
    {
        app()->instance('current.workspace', $workspace);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'members')
            ->withPivot('role');
    }

    public function memberByUser(User $user): ?Member
    {
        return $this->members()->where('user_id', $user->id)->first();
    }

    public function hasUser(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function roleOf(User $user): ?WorkspaceRole
    {
        $member = $this->memberByUser($user);

        return $member?->role;
    }
}
