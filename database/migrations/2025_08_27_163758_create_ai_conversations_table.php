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
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->text('message');
            $table->text('response');
            $table->enum('message_type', ['user', 'ai'])->default('user');
            $table->timestamps();

            // Clé étrangère
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Index pour optimiser les requêtes
            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_conversations');
    }
};
