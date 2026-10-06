<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'pix_name')) {
                $table->string('pix_name')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'pix_type')) {
                $table->string('pix_type')->nullable()->after('pix_name');
            }
            if (!Schema::hasColumn('users', 'pix_key')) {
                $table->string('pix_key')->nullable()->after('pix_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['pix_name', 'pix_type', 'pix_key'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
