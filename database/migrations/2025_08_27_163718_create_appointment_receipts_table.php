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
        Schema::create('appointment_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('receipt_number', 50)->unique();
            $table->string('pdf_path', 500)->nullable();
            $table->string('qr_code', 500)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->boolean('downloaded_by_patient')->default(false);
            $table->boolean('downloaded_by_doctor')->default(false);
            $table->timestamps();

            // Clé étrangère
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            
            // Index
            $table->index('receipt_number');
            $table->index('appointment_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointment_receipts');
    }
};
