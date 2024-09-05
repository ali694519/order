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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('OrderId');
            $table->foreign('OrderId')
                ->references('Id')
                ->on('Orders')
                ->cascadeOnDelete();
            $table->decimal('AmountPaid');
            $table->integer('PaymentMethod')->default(0);
            $table->dateTime('PaymentDate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
