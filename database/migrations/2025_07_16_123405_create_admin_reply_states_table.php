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
        Schema::create('admin_reply_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appeal_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('admin_id');           // Telegram ID
            $table->unsignedBigInteger('user_id');            // Foydalanuvchi Telegram ID
            $table->timestamps();
        });
    }
    
    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('admin_reply_states');
    }
};
