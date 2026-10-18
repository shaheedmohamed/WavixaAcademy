<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('status')->default('active')->index()->after('avatar');
            $table->string('locale', 8)->default('ar')->after('status');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('student_number')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'avatar', 'status', 'locale', 'last_login_at', 'student_number']);
        });
    }
};
