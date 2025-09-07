<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('specialty_id')->nullable();
            $table->string('license_number', 50)->unique()->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->decimal('consultation_fee', 10, 2)->nullable();
            $table->text('cabinet_address')->nullable();
            $table->string('cabinet_phone', 20)->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('specialty_id')->references('id')->on('specialties')->onDelete('set null');
            
            // Index pour optimiser les recherches
            $table->index(['specialty_id', 'is_verified']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('doctors');
    }
};
