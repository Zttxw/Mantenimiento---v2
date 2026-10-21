<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_device_id')->constrained()->cascadeOnDelete();
            $table->string('sync_type', 30);   // heartbeat, snapshot, change
            $table->jsonb('payload_json')->nullable();
            $table->jsonb('detected_changes_json')->nullable();
            $table->string('status', 20)->default('recibido');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->index('agent_device_id', 'synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_syncs');
    }
};
