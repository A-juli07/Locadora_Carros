<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('modelo_id');
            $table->string('placa', 10);
            $table->boolean('disponivel');
            $table->integer('km');
            $table->timestamps();

            $table->foreign('modelo_id')->references('id')->on('modelos');
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('carros');
    }
};
