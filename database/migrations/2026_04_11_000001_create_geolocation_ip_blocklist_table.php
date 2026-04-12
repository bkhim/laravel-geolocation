<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geolocation_ip_blocklist', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->string('reason')->nullable();
            $table->timestamp('blocked_until');
            $table->integer('attempts')->default(1);
            $table->timestamps();

            $table->index('blocked_until');
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geolocation_ip_blocklist');
    }
};