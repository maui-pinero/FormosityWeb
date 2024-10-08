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
        Schema::table('orders',function(Blueprint $table) {
            $table->string('coupon_code_id')->nullable()->after('coupon_code');
            $table->enum('payment_status',['paid','unpaid'])->after('grand_total')->default('unpaid');
            $table->enum('status',['pending','shipped','delivered','cancelled'])->after('payment_status')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders',function(Blueprint $table) {
            $table->dropColumn('coupon_code_id');
            $table->dropColumn('payment_status');
            $table->dropColumn('status');
        });
    }
};
