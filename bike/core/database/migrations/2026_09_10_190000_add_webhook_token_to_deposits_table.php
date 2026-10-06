<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('deposits', 'webhook_token')) {
                $table->string('webhook_token', 64)->nullable()->after('gateway_identifier');
            }
        });
    }

    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'webhook_token')) {
                $table->dropColumn('webhook_token');
            }
        });
    }
};
