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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')
                  ->unique()
                  ->constrained('clients')
                  ->restrictOnDelete();
            $table->string('number')->unique();
            $table->string('agency')->default('0001');
            $table->bigInteger('balance_cents')->default(0);
            $table->enum('status', ['active','blocked','closed',])->default('active');
            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE accounts
             ADD CONSTRAINT accounts_balance_cents_non_negative
             CHECK (balance_cents >= 0)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
