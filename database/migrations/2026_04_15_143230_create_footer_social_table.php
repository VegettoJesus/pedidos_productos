<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFooterSocialTable extends Migration
{
    public function up()
    {
        Schema::create('footer_social', function (Blueprint $table) {
            $table->id();
            $table->foreignId('column_id')->constrained('footer_columns')->onDelete('cascade');
            $table->string('name', 50)->nullable();
            $table->string('icon', 255);
            $table->string('url', 255);
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('footer_social');
    }
}