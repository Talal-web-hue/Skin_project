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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('order_date');
            $table->enum('status' , ['confirmed' , 'cancelled' , 'pending'])->default('confirmed');
            $table->decimal('subtotal' , 10 ,2);   // مجموع المنتجات قبل الخصم
            $table->decimal('discount' , 10 ,2);  // قيمة الخصم 
            $table->decimal('total_amount' , 10 ,2);  // المبلغ النهائي بعد الخصم
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
