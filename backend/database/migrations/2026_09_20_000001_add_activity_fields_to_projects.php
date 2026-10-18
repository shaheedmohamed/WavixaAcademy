<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('after_lesson_id')->nullable()->after('module_id')
                ->constrained('lessons')->nullOnDelete();
            $table->string('attachment_type')->nullable()->after('instructions'); // image | video | link
            $table->string('attachment_url', 1000)->nullable()->after('attachment_type');
        });

        Schema::table('project_submissions', function (Blueprint $table) {
            $table->string('link_url', 500)->nullable()->after('live_demo_url');
            $table->string('file_name')->nullable()->after('file_path');
            $table->string('file_mime', 120)->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('after_lesson_id');
            $table->dropColumn(['attachment_type', 'attachment_url']);
        });

        Schema::table('project_submissions', function (Blueprint $table) {
            $table->dropColumn(['link_url', 'file_name', 'file_mime']);
        });
    }
};
