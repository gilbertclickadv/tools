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
        Schema::create('stored_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ip_address')->index();
            $table->string('profile_type');
            $table->text('summary_payload');
            $table->string('foreground_color')->default('#8B5CF6');
            $table->string('background_color')->default('#FFFFFF');
            $table->unsignedInteger('matrix_size')->default(280);
            $table->string('redundancy_level')->default('H');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stored_qr_codes');
    }
};
