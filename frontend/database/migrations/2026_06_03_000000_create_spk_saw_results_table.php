<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spk_saw_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained()->cascadeOnDelete();

            $table->decimal('c1_score', 8, 4)->nullable()->comment('Financial Accuracy');
            $table->decimal('c2_score', 8, 4)->nullable()->comment('Legality Completeness');
            $table->decimal('c3_score', 8, 4)->nullable()->comment('SLA Timeliness (cost raw)');
            $table->decimal('c4_score', 8, 4)->nullable()->comment('Attribute Conformity');
            $table->decimal('c5_score', 8, 4)->nullable()->comment('Partner History');

            $table->decimal('c1_normalized', 8, 4)->nullable();
            $table->decimal('c2_normalized', 8, 4)->nullable();
            $table->decimal('c3_normalized', 8, 4)->nullable();
            $table->decimal('c4_normalized', 8, 4)->nullable();
            $table->decimal('c5_normalized', 8, 4)->nullable();

            $table->decimal('preference_value', 8, 4)->nullable();
            $table->string('recommendation', 32)->nullable();

            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spk_saw_results');
    }
};
