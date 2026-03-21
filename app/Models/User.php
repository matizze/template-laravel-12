<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password', 'role'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'members')
            ->using(Member::class)
            ->withPivot('id', 'role')
            ->withCasts(['role' => WorkspaceRole::class])
            ->withTimestamps();
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    /** @var array<int, WorkspaceRole|null> */
    private array $roleCache = [];

    public function roleIn(Workspace $workspace): ?WorkspaceRole
    {
        if (array_key_exists($workspace->id, $this->roleCache)) {
            return $this->roleCache[$workspace->id];
        }

        /** @var Member|null $member */
        $member = $workspace->memberships()
            ->where('user_id', $this->id)
            ->first();

        /** @var WorkspaceRole|null $role */
        $role = $member?->role;

        return $this->roleCache[$workspace->id] = $role;
    }

    public function isMemberOf(Workspace $workspace): bool
    {
        return $this->roleIn($workspace) !== null;
    }
}
