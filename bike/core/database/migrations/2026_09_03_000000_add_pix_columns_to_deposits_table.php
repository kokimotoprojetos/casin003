<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('deposits', 'pix_code')) {
                $table->text('pix_code')->nullable()->after('detail');
            }
            if (!Schema::hasColumn('deposits', 'qr_code_url')) {
                $table->text('qr_code_url')->nullable()->after('pix_code');
            }
            if (!Schema::hasColumn('deposits', 'gateway_txid')) {
                $table->string('gateway_txid')->nullable()->after('qr_code_url');
            }
        });
    }

    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            foreach (['pix_code', 'qr_code_url', 'gateway_txid'] as $col) {
                if (Schema::hasColumn('deposits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
