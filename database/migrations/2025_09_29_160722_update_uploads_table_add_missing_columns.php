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
        Schema::table('uploads', function (Blueprint $table): void {
            $table->string('uuid')->unique()->after('id');
            $table->string('mime_type')->after('original_filename');
            $table->integer('uploaded_chunks')->default(0)->after('total_chunks');
            $table->json('chunk_info')->nullable()->after('checksum');
            $table->string('storage_path')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('storage_path');

            // Remove old column if it exists
            if (Schema::hasColumn('uploads', 'upload_path')) {
                $table->dropColumn('upload_path');
            }
            if (Schema::hasColumn('uploads', 'filename')) {
                $table->dropColumn('filename');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table): void {
            $table->dropColumn(['uuid', 'mime_type', 'uploaded_chunks', 'chunk_info', 'storage_path', 'completed_at']);
            $table->string('filename')->after('id');
            $table->string('upload_path')->nullable()->after('status');
        });
    }
};
