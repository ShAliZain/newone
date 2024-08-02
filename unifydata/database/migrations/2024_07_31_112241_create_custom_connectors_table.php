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
        Schema::create('custom_connectors', function (Blueprint $table) {
            $table->id();
            $table->string('base_url');
            $table->string('auth_type');
            $table->json('auth_details')->nullable();
            $table->string('stream'); // JSON column for storing the stream data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_connectors');
    }
};
