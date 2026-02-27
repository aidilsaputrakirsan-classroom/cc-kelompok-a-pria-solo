<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ground_truths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->string('doc_type', 50);
            $table->json('extracted_data');
            $table->timestamps();
            
            $table->unique(['ticket_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ground_truths');
    }
};  