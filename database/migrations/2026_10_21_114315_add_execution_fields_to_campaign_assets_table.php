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
        Schema::table('campaign_assets', function (Blueprint $table) {
            $table->string('attended_type', 20)->nullable()->after('status'); // 'agente', 'manual'
            $table->foreignId('attended_by_employee_id')->nullable()->after('attended_type')->constrained('employees')->nullOnDelete();
            $table->json('checklist_tasks')->nullable()->after('attended_by_employee_id');
            $table->string('asset_condition_after', 30)->nullable()->after('checklist_tasks');

            $table->index('attended_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_assets', function (Blueprint $table) {
            $table->dropForeign(['attended_by_employee_id']);
            $table->dropIndex(['attended_type']);
            $table->dropColumn(['attended_type', 'attended_by_employee_id', 'checklist_tasks', 'asset_condition_after']);
        });
    }
};
