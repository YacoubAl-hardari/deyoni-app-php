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
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->string('direction')->default('payable');
        });
        Schema::create('payment_promises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creditor_id')->constrained()->restrictOnDelete();
            $table->string('direction')->default('receivable');
            $table->string('currency');
            $table->unsignedBigInteger('amount_minor');
            $table->date('due_at')->index();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('ledger_entry_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_promises');
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropColumn('direction');
        });
    }
};
