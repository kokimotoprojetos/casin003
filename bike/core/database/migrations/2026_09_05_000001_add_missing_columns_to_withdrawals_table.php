<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            if (!Schema::hasColumn('withdrawals', 'method_id')) {
                $table->bigInteger('method_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('withdrawals', 'currency')) {
                $table->string('currency', 10)->nullable()->after('method_code');
            }
            if (!Schema::hasColumn('withdrawals', 'rate')) {
                $table->decimal('rate', 18, 2)->default(1)->after('currency');
            }
            if (!Schema::hasColumn('withdrawals', 'final_amount')) {
                $table->decimal('final_amount', 18, 2)->nullable()->after('rate');
            }
            if (!Schema::hasColumn('withdrawals', 'after_charge')) {
                $table->decimal('after_charge', 18, 2)->nullable()->after('final_amount');
            }
            if (!Schema::hasColumn('withdrawals', 'trx')) {
                $table->string('trx', 50)->nullable()->after('after_charge');
            }
            if (!Schema::hasColumn('withdrawals', 'withdraw_information')) {
                $table->text('withdraw_information')->nullable()->after('trx');
            }
            if (!Schema::hasColumn('withdrawals', 'admin_feedback')) {
                $table->text('admin_feedback')->nullable()->after('withdraw_information');
            }
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $columns = ['method_id', 'currency', 'rate', 'final_amount',
                'after_charge', 'trx', 'withdraw_information', 'admin_feedback'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('withdrawals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
