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
        Schema::create('bulk_import_results', function (Blueprint $table): void {
            $table->id();
            $table->string('import_type'); // products, users, etc.
            $table->string('filename');
            $table->integer('total_rows');
            $table->integer('imported_rows')->default(0);
            $table->integer('updated_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->json('errors')->nullable(); // Store validation errors
            $table->string('status')->default('processing'); // processing, completed, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_import_results');
    }
};
