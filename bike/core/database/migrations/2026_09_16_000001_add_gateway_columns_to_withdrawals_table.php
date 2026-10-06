<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('gateway_withdraw_id', 64)->nullable()->after('admin_feedback');
            $table->string('gateway_webhook_token', 64)->nullable()->after('gateway_withdraw_id');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['gateway_withdraw_id', 'gateway_webhook_token']);
        });
    }
};
