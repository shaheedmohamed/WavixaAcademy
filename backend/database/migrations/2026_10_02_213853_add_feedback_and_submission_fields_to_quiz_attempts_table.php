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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('submission_type', 32)->default('mcq')->after('answers'); // mcq, task
            $table->text('student_notes')->nullable()->after('submission_type');
            $table->string('image_url', 500)->nullable()->after('student_notes');
            $table->string('link_url', 500)->nullable()->after('image_url');
            $table->string('status', 32)->default('submitted')->after('passed'); // submitted, changes_requested, approved
            $table->text('feedback')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('feedback');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'submission_type',
                'student_notes',
                'image_url',
                'link_url',
                'status',
                'feedback',
                'reviewed_at',
                'reviewed_by',
            ]);
        });
    }
};
