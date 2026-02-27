<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyProjessLogsTableForPolymorphicTracking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('1_logs', function (Blueprint $table) {
            // Polymorphic tracking fields untuk berbagai model
            $table->string('trackable_type', 100)->nullable()->after('task_id');
            $table->unsignedBigInteger('trackable_id')->nullable()->after('trackable_type');
            
            // Field untuk menyimpan perubahan dari berbagai model
            $table->string('model_type', 100)->nullable()->after('trackable_id')->comment('Model yang di-track: projess_task, document, obl, dll');
            $table->unsignedBigInteger('model_id')->nullable()->after('model_type')->comment('ID dari model yang di-track');
            
            // Field untuk menyimpan perubahan status/state
            $table->string('from_status')->nullable()->after('to_task_parent');
            $table->string('to_status')->nullable()->after('from_status');
            
            // Field untuk menyimpan perubahan field lainnya (JSON)
            $table->json('changed_fields')->nullable()->after('to_status')->comment('Field yang berubah dalam format JSON');
            
            // Indexes untuk polymorphic relationship
            $table->index(['trackable_type', 'trackable_id']);
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('1_logs', function (Blueprint $table) {
            $table->dropIndex(['trackable_type', 'trackable_id']);
            $table->dropIndex(['model_type', 'model_id']);
            
            $table->dropColumn([
                'trackable_type',
                'trackable_id',
                'model_type',
                'model_id',
                'from_status',
                'to_status',
                'changed_fields'
            ]);
        });
    }
}
