<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'user_id', 'email', 'role', 'token', 'accepted_at'];

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
            'accepted_at' => 'timestamp',
            'created_at' => 'timestamp',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function accept(User $user): void
    {
        $this->update([
            'user_id' => $user->id,
            'accepted_at' => now(),
        ]);

        Member::create([
            'user_id' => $user->id,
            'workspace_id' => $this->workspace_id,
            'role' => $this->role,
        ]);
    }
}
