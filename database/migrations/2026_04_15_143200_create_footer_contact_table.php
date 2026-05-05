<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFooterContactTable extends Migration
{
    public function up()
    {
        Schema::create('footer_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('column_id')->unique()->constrained('footer_columns')->onDelete('cascade');
            $table->string('phone', 20)->nullable();
            $table->string('phone_icon', 255)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('email_icon', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('address_icon', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('footer_contact');
    }
}