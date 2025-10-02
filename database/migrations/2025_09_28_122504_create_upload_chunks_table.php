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

        Schema::create('upload_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('upload_id')->constrained()->onDelete('cascade');
            $table->integer('chunk_number');
            $table->integer('size');
            $table->string('checksum');
            $table->enum('status', ['pending', 'uploaded', 'verified', 'failed'])->default('pending');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->unique(['upload_id', 'chunk_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_chunks');
    }
};
