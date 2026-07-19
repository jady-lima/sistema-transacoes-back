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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                  ->constrained('accounts')
                  ->restrictOnDelete();
            $table->enum('direction', ['credit', 'debit',]);
            $table->enum('operation', ['deposit', 'withdrawal', 'transfer',]);
            $table->bigInteger('amount_cents');
            
            $table->uuid('reference_id')
                ->nullable()
                ->index();

            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE transactions
             ADD CONSTRAINT transactions_amount_cents_positive
             CHECK (amount_cents > 0)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
