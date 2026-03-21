<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('workspace_id')->constrained('workspaces')->onDelete('cascade');
            $table->enum('role', ['owner', 'admin', 'member', 'viewer']);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'workspace_id']);
            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
