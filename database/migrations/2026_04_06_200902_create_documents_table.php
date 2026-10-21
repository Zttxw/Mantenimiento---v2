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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50);     // ficha_tecnica, acta_mantenimiento, etc.
            $table->string('reference_type', 50);     // asset, maintenance_case, campaign, movement
            $table->unsignedBigInteger('reference_id');
            $table->string('code', 50)->nullable();
            $table->string('title');
            $table->string('file_path');
            $table->unsignedBigInteger('generated_by');
            $table->timestamp('generated_at');
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();

            $table->foreign('generated_by')->references('id')->on('users');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
