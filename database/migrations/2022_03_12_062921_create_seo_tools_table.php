<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_tools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lang_id');
            $table->string('site_title');
            $table->string('home_title');
            $table->text('site_description');
            $table->text('keyword');
            $table->text('google_analytics');
            $table->foreign('lang_id')->references('id')->on('languages');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_tools');
    }
};
