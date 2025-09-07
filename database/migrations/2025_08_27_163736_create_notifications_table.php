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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 200);
            $table->text('message');
            $table->string('type', 50)->default('info'); // info, success, warning, error
            $table->boolean('is_read')->default(false);
            $table->unsignedBigInteger('related_appointment_id')->nullable();
            $table->timestamps();

            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('related_appointment_id')->references('id')->on('appointments')->onDelete('set null');
            
            // Index pour optimiser les requêtes
            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
