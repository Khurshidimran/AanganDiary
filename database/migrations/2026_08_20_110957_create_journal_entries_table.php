<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entry_number', 30)->unique();
            $table->date('entry_date');
            $table->string('type', 20);
            $table->string('source', 20)->default('manual');
            $table->string('status', 20)->default('posted');
            // Explicitly short (rather than the default 255) — a composite
            // index across two default-length string columns exceeds the
            // key-length limit on some MySQL hosts (seen in production:
            // "max key length is 1000 bytes", likely an older row format /
            // storage engine default there vs. local dev).
            $table->string('reference_type', 40)->nullable();
            $table->string('reference_id', 40)->nullable();
            $table->text('narration')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('voided_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
            $table->index('entry_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
