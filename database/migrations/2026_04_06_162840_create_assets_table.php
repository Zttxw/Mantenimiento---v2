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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('internal_code', 50)->unique();
            $table->string('patrimonial_code', 50)->nullable()->unique();
            $table->string('name');
            $table->string('asset_type', 50);           // Enum AssetType
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('status', 30)->default('disponible');      // Enum AssetStatus
            $table->string('condition', 30)->default('bueno');        // Enum AssetCondition
            $table->foreignId('organizational_unit_id')
                ->nullable()->constrained()->nullOnDelete();
            $table->foreignId('responsible_employee_id')
                ->nullable()->constrained('employees')->nullOnDelete();
            // $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('purchase_date')->nullable();
            $table->decimal('reference_value', 12, 2)->nullable(); // valor de compra
            $table->jsonb('specs_json')->nullable();     // CPU, RAM, disco, etc.
            $table->jsonb('extra_json')->nullable();     // Garantía, licencias, etc.
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('asset_type');
            $table->index('status');
            $table->index('organizational_unit_id');
            $table->index('responsible_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
