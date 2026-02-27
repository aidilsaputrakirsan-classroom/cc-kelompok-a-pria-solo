<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_review_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ground_truth_id')->constrained()->onDelete('cascade');
            $table->string('doc_type', 50);
            $table->string('status', 20);
            $table->text('error_message')->nullable();
            $table->json('review_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_review_results');
    }
};