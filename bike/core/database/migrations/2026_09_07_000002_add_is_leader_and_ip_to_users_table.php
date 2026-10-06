<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_leader')) {
                $table->boolean('is_leader')->default(0)->after('status');
            }
            if (!Schema::hasColumn('users', 'ip')) {
                $table->string('ip', 45)->nullable()->after('is_leader');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_leader')) {
                $table->dropColumn('is_leader');
            }
            if (Schema::hasColumn('users', 'ip')) {
                $table->dropColumn('ip');
            }
        });
    }
};
