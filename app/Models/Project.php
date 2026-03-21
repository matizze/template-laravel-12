<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $fillable = ['name', 'description', 'user_id', 'workspace_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
