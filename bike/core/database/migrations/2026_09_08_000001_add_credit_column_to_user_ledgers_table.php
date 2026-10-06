<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_ledgers')) {
            Schema::table('user_ledgers', function (Blueprint $table) {
                if (!Schema::hasColumn('user_ledgers', 'credit')) {
                    $table->decimal('credit', 18, 2)->default(0)->after('amount');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_ledgers', function (Blueprint $table) {
            $table->dropColumn('credit');
        });
    }
};
