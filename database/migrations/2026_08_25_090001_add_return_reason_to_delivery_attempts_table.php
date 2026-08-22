<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_attempts', function (Blueprint $table) {
            $table->string('return_reason')->nullable()->after('failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_attempts', function (Blueprint $table) {
            $table->dropColumn('return_reason');
        });
    }
};
