<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Altera a foreign key de workspace_id na tabela projects de SET NULL para CASCADE.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->foreignId('workspace_id')->nullable()->change();
            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverte para SET NULL.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->foreignId('workspace_id')->nullable()->change();
            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->nullOnDelete();
        });
    }
};
