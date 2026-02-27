<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bounding_boxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boundable_id');
            $table->string('boundable_type');
            $table->integer('page');
            $table->string('word');
            $table->decimal('x', 10, 4);
            $table->decimal('y', 10, 4);
            $table->decimal('width', 10, 4);
            $table->decimal('height', 10, 4);
            $table->timestamps();

            $table->index(['boundable_id', 'boundable_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bounding_boxes');
    }
};