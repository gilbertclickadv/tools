<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_url_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_url_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('country', 64)->nullable();
            $table->string('referrer', 512)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('device_type', 20)->nullable();  // desktop / mobile / tablet / bot
            $table->timestamp('clicked_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_url_clicks');
    }
};
