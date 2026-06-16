<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_penerbangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
            $table->string('flight_arr', 20)->nullable();
            $table->string('flight_dep', 20)->nullable();
            $table->string('origin', 10)->nullable();
            $table->string('destination', 10)->nullable();
            $table->string('route', 50)->nullable();
            $table->string('aircraft_type', 30)->nullable();
            $table->string('registrasi_pesawat', 30)->nullable();
            $table->time('sched_arrival')->nullable();
            $table->time('sched_departure')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['mitra_jasa_id', 'flight_arr']);
            $table->index(['mitra_jasa_id', 'flight_dep']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_penerbangan');
    }
};
