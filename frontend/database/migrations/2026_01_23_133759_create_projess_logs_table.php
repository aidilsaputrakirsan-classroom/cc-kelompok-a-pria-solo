<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjessLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop table if exists to avoid conflicts
        Schema::dropIfExists('1_logs');
        
        Schema::create('1_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // Task yang dipindahkan/diubah
            $table->unsignedBigInteger('task_id');
            
            // Project identifier (ID_RSO dari projects)
            $table->string('id_rso', 50)->nullable();
            
            // Tipe aksi: proceed, return, create, update, delete, move, reorder
            $table->string('action_type', 50);
            
            // Task sebelumnya (dari)
            $table->unsignedBigInteger('from_task_id')->nullable();
            $table->integer('from_task_order')->nullable();
            $table->unsignedBigInteger('from_task_parent')->nullable();
            
            // Task setelahnya (ke)
            $table->unsignedBigInteger('to_task_id')->nullable();
            $table->integer('to_task_order')->nullable();
            $table->unsignedBigInteger('to_task_parent')->nullable();
            
            // Catatan/komentar perubahan
            $table->text('notes')->nullable();
            
            // User yang melakukan perubahan
            $table->unsignedInteger('user_id')->nullable();
            
            // Metadata tambahan (JSON untuk fleksibilitas)
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes - semua index didefinisikan di sini untuk menghindari duplicate
            $table->index('task_id');
            $table->index('id_rso');
            $table->index('action_type');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('1_logs');
    }
}
