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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->morphs('reviewable');  // يضيف reviewable_id و reviewable_type        
            // $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('comment')->nullable()->comment('تعليق المستخدم على الخدمة أو المنتج');
            $table->decimal('rating' , 3 , 2)->nullable()->comment('متوسط التقييمات');            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
