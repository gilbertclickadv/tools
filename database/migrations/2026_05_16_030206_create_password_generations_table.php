<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->index();
            $table->unsignedTinyInteger('length')->default(16);
            $table->boolean('use_uppercase')->default(true);
            $table->boolean('use_lowercase')->default(true);
            $table->boolean('use_numbers')->default(true);
            $table->boolean('use_symbols')->default(false);
            $table->timestamp('generated_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_generations');
    }
};
