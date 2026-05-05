<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFooterLinksTable extends Migration
{
    public function up()
    {
        Schema::create('footer_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('column_id')->constrained('footer_columns')->onDelete('cascade');
            $table->string('text', 100);
            $table->string('url', 255);
            $table->string('icon', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('footer_links');
    }
}