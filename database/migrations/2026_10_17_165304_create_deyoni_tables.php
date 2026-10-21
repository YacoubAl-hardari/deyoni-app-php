<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('creditors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('phone')->nullable();
            $table->string('icon')->default('person');
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
        });
        DB::statement("CREATE TABLE ledger_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            creditor_id INTEGER NOT NULL REFERENCES creditors(id) ON DELETE RESTRICT,
            kind TEXT NOT NULL CHECK(kind IN ('debt','payment')),
            amount_minor INTEGER NOT NULL CHECK(typeof(amount_minor) = 'integer' AND amount_minor > 0 AND amount_minor <= 1000000000000),
            currency TEXT NOT NULL CHECK(currency IN ('YER','SAR')),
            occurred_at TEXT NOT NULL,
            notes TEXT NULL,
            request_id TEXT NOT NULL UNIQUE,
            replaces_entry_id INTEGER NULL UNIQUE REFERENCES ledger_entries(id) ON DELETE RESTRICT,
            voided_at TEXT NULL,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )");
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->index(['creditor_id', 'currency', 'voided_at']);
            $table->index(['creditor_id', 'occurred_at', 'id']);
        });
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('action');
            $table->text('before_data')->nullable();
            $table->text('after_data')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at');
        });
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('creditors');
        Schema::dropIfExists('app_settings');
    }
};
