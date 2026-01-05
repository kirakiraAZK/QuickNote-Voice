<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Judul Meeting
            $table->string('language'); // Bahasa (id-ID, en-US, dll)
            
            // Gunakan longText untuk menampung teks yang sangat panjang
            $table->longText('transcript'); 
            
            // Summary bisa kosong jika AI gagal atau user tidak meminta summary
            $table->text('summary')->nullable(); 

            $table->string('audio_path')->nullable();
            
            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};