<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('deposits', 'gateway_identifier')) {
                $table->string('gateway_identifier', 255)->nullable()->after('gateway_txid');
            }
        });
    }

    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'gateway_identifier')) {
                $table->dropColumn('gateway_identifier');
            }
        });
    }
};