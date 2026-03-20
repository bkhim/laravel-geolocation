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
        Schema::create('user_login_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip', 45);             // masked IP
            $table->string('ip_hash', 64)->nullable(); // hashed full IP
            $table->char('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->boolean('is_proxy')->default(false);
            $table->boolean('is_tor')->default(false);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index('country_code');
            $table->index('ip_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_login_locations');
    }
};