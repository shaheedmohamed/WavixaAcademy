<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover')->nullable();
            $table->string('type')->default('webinar')->index();
            $table->string('visibility')->default('public')->index();
            $table->timestamp('start_at')->index();
            $table->timestamp('end_at')->nullable();
            $table->string('timezone')->default('Africa/Cairo');
            $table->string('location')->nullable();
            $table->string('online_url')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status')->default('published')->index();
            $table->string('speaker')->nullable();
            $table->string('recording_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
