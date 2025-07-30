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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method');
            $table->string('url');
            $table->jsonb('headers')->nullable(); // Stores request headers as JSONB
            $table->jsonb('body')->nullable();    // Stores request body as JSONB
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('status_code')->nullable();
            $table->jsonb('response_body')->nullable(); // Stores response body as JSONB
            $table->timestamp('requested_at');    // Timestamp when request was received
            $table->timestamp('responded_at')->nullable(); // Timestamp when response was sent
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
