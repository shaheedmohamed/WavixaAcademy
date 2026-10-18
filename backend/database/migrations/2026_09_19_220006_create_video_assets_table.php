<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('mux');
            $table->string('google_file_id')->nullable()->index();
            $table->string('source_filename')->nullable();
            $table->string('provider_asset_id')->nullable()->index();
            $table->string('provider_upload_id')->nullable();
            $table->string('playback_id')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_assets');
    }
};
