<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->index();
            $table->string('code', 12)->unique()->index();   // the short code e.g. "aB3xZ"
            $table->text('original_url');
            $table->string('title', 255)->nullable();        // optional custom label
            $table->string('alias', 64)->nullable()->unique(); // optional custom slug
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedBigInteger('click_count')->default(0); // denormalized fast counter
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_urls');
    }
};
