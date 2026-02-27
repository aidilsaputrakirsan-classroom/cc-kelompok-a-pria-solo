<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutoDraftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auto_drafts', function (Blueprint $table) {
            $table->id();
            
            // RSO Identifier (unique project identifier)
            $table->string('id_rso')->unique();
            
            // P1 General Information
            $table->string('judul_p1')->nullable();
            $table->string('nomor_p1')->nullable();
            $table->date('tanggal_p1')->nullable();
            
            // Customer Information
            $table->string('pelanggan')->nullable();
            
            // Draft metadata
            $table->enum('status', ['draft', 'processing', 'completed'])->default('draft');
            
            $table->timestamps();
            
            // Index for faster lookups
            $table->index('id_rso');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auto_drafts');
    }
}
