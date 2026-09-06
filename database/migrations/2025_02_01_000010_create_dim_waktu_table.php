<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Dimensi periode publikasi data — semester & tahun (KNF-30: menampung periode baru tanpa mengubah struktur). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dim_waktu', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('semester');
            $table->string('label', 20);
            $table->timestamps();
            $table->unique(['tahun', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_waktu');
    }
};
