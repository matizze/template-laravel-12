<?php

namespace Modules\Workspace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\User\Models\User;
use Modules\Workspace\Database\Factories\MemberFactory;
use Modules\Workspace\Enums\WorkspaceRole;

class Member extends Pivot
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected $table = 'members';

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }

    public $incrementing = true;

    protected $fillable = ['user_id', 'workspace_id', 'role'];

    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
