<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_purchases', fn (Blueprint $table) => $table->boolean('email_receipt')->default(false));
    }

    public function down(): void
    {
        Schema::table('payment_purchases', fn (Blueprint $table) => $table->dropColumn('email_receipt'));
    }
};
