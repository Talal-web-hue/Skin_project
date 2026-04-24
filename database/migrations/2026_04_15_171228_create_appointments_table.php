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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->OnDelete('cascade'); // cascade تعني بحذف العميل تُحذف مواعيده
            $table->foreignId('specialist_id')->constrained('specialists')->onDelete('restrict'); // restrict تعني حماية سجلات المواعيد عند حذف الأخصائي
            $table->foreignId('service_id')->constrained('services')->onDelete('restrict');
            $table->dateTime('start_date'); // dateTime لدعم الأوقات و التواريخ بدقة 
            $table->dateTime('end_date'); // dateTime لدعم الأوقات و التواريخ بدقة 
            $table->enum('status' , ['completed' , 'confirmed' , 'cancelled' , 'pending' , '']);  // pending أي بانتظار التأكيد
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
