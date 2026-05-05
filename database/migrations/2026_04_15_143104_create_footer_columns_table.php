<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFooterColumnsTable extends Migration
{
    public function up()
    {
        Schema::create('footer_columns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->enum('column_type', ['links', 'mixed'])->default('links');
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->string('icon', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('footer_columns');
    }
}